import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { defineComponent, h } from 'vue'

import LessonTask from '@/components/lesson/LessonTask.vue'
import type { RunnerOutcome } from '@/runner/client'
import type { CompileResponse, TestResponse } from '@/runner/protocol'

import { helloTask } from './lessonFixtures'

const runner = {
  preload: vi.fn<() => void>(),
  test: vi.fn<() => Promise<RunnerOutcome<TestResponse>>>(),
  compile: vi.fn<() => Promise<RunnerOutcome<CompileResponse>>>(),
}

vi.mock('@/composables/useRunner', () => ({ useRunner: () => runner }))

// Вместо CodeMirror — textarea: тестируем задачу, а не редактор
vi.mock('@/components/lesson/CodeEditor.vue', () => ({
  // __esModule: иначе defineAsyncComponent примет весь модуль за компонент
  __esModule: true,
  default: defineComponent({
    props: { modelValue: { type: String, required: true }, label: String, diagnostics: Array },
    emits: ['update:modelValue'],
    setup:
      (props, { emit }) =>
      () =>
        h('textarea', {
          value: props.modelValue,
          'aria-label': props.label,
          onInput: (event: Event) =>
            emit('update:modelValue', (event.target as HTMLTextAreaElement).value),
        }),
  }),
}))

function done(response: Partial<TestResponse>): RunnerOutcome<TestResponse> {
  return {
    status: 'done',
    response: { compiled: true, diagnostics: [], passed: false, results: [], ...response },
  }
}

async function mountTask() {
  const wrapper = mount(LessonTask, { props: { task: helloTask, draftKey: 'test/hello' } })
  await flushPromises()
  return wrapper
}

const runButton = (wrapper: Awaited<ReturnType<typeof mountTask>>) =>
  wrapper.findAll('button').find((button) => button.text() === 'Запустить и проверить')!

describe('LessonTask', () => {
  beforeEach(() => {
    localStorage.clear()
    runner.test.mockReset()
    runner.compile
      .mockReset()
      .mockResolvedValue({ status: 'done', response: { compiled: true, diagnostics: [] } })
  })

  it('до запуска — сколько будет проверок; стартовый код в редакторе', async () => {
    const wrapper = await mountTask()

    expect(wrapper.text()).toContain(
      'Нажми «Запустить и проверить»: код скомпилируется и пройдёт 2 проверки',
    )
    expect(wrapper.get('textarea').element.value).toBe('using System;\n')
  })

  it('проваленная проверка — подпись, сообщение из задачи и вывод программы', async () => {
    runner.test.mockResolvedValue(
      done({
        results: [
          {
            index: 0,
            passed: false,
            expected: 'Привет, мир!',
            actual: 'Привет мир!',
            outputLimitExceeded: false,
            error: null,
          },
          {
            index: 1,
            passed: true,
            expected: null,
            actual: '1',
            outputLimitExceeded: false,
            error: null,
          },
        ],
      }),
    )
    const wrapper = await mountTask()

    await runButton(wrapper).trigger('click')
    await flushPromises()

    const text = wrapper.text()
    expect(text).toContain('Пройдено 1 из 2')
    expect(text).toContain('✓ Программа компилируется')
    expect(text).toContain('✗ Программа печатает то, что нужно')
    expect(text).toContain('Программа должна напечатать ровно «Привет, мир!».')
    expect(text).toContain('✓ Вызывается Console.WriteLine')
    expect(text).toContain('Привет мир!')
    expect(wrapper.emitted('passed')).toBeUndefined()
  })

  it('ошибка компиляции — объяснение по-русски со строкой и оригиналом', async () => {
    runner.test.mockResolvedValue(
      done({
        compiled: false,
        diagnostics: [
          {
            code: 'CS1002',
            severity: 'error',
            message: '; expected',
            arguments: [],
            line: 3,
            column: 34,
            endLine: 3,
            endColumn: 34,
          },
        ],
      }),
    )
    const wrapper = await mountTask()

    await runButton(wrapper).trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Строка 3: Не хватает точки с запятой «;».')
    expect(wrapper.text()).toContain('CS1002: ; expected')
  })

  it('все проверки пройдены — событие passed и кнопка «Дальше»', async () => {
    runner.test.mockResolvedValue(
      done({
        passed: true,
        results: [
          {
            index: 0,
            passed: true,
            expected: 'Привет, мир!',
            actual: 'Привет, мир!\n',
            outputLimitExceeded: false,
            error: null,
          },
          {
            index: 1,
            passed: true,
            expected: null,
            actual: '1',
            outputLimitExceeded: false,
            error: null,
          },
        ],
      }),
    )
    const wrapper = await mountTask()

    await runButton(wrapper).trigger('click')
    await flushPromises()
    await wrapper
      .findAll('button')
      .find((button) => button.text() === 'Дальше →')!
      .trigger('click')

    expect(wrapper.text()).toContain('Все проверки пройдены!')
    expect(wrapper.emitted('passed')).toHaveLength(1)
    expect(wrapper.emitted('next')).toHaveLength(1)
  })

  it('бесконечный цикл — объяснение про таймаут', async () => {
    runner.test.mockResolvedValue({ status: 'timeout' })
    const wrapper = await mountTask()

    await runButton(wrapper).trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Возможно, в ней бесконечный цикл')
  })

  it('подсказки открываются по одной, не больше трёх', async () => {
    const wrapper = await mountTask()
    const hintButton = () =>
      wrapper.findAll('button').find((button) => button.text().includes('подсказк'))

    await hintButton()!.trigger('click')
    expect(hintButton()!.text()).toBe('Ещё подсказка (1 из 3)')
    await hintButton()!.trigger('click')
    await hintButton()!.trigger('click')

    expect(wrapper.text()).toContain('Подсказка 3')
    expect(hintButton()).toBeUndefined()
  })

  it('код сохраняется черновиком, «Сбросить» возвращает стартовый', async () => {
    const wrapper = await mountTask()
    await wrapper.get('textarea').setValue('Console.Write(1);')
    expect(localStorage.getItem('draft:test/hello')).toBe('Console.Write(1);')

    const again = await mountTask()
    expect(again.get('textarea').element.value).toBe('Console.Write(1);')

    await again
      .findAll('button')
      .find((button) => button.text() === 'Сбросить')!
      .trigger('click')
    expect(again.get('textarea').element.value).toBe('using System;\n')
    expect(localStorage.getItem('draft:test/hello')).toBeNull()
  })
})
