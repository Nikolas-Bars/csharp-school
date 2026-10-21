import { flushPromises } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { ref } from 'vue'

import { ApiError } from '@/api/http'
import { useLoader } from '@/composables/useLoader'
import { describeCheck } from '@/runner/explain'

describe('useLoader', () => {
  it('ответ на устаревший запрос не затирает новый', async () => {
    const source = ref('first')
    const pending: Record<string, (value: string) => void> = {}
    const { data, loading } = useLoader(
      source,
      (value) => new Promise<string>((resolve) => (pending[value] = resolve)),
    )

    source.value = 'second'
    await flushPromises()
    pending.second!('второй')
    pending.first!('первый')
    await flushPromises()

    expect(data.value).toBe('второй')
    expect(loading.value).toBe(false)
  })

  it('ошибка API — в error, данных нет', async () => {
    const { data, error } = useLoader(ref(1), () =>
      Promise.reject(new ApiError('Такого курса нет.', 404)),
    )
    await flushPromises()

    expect(data.value).toBeNull()
    expect(error.value?.message).toBe('Такого курса нет.')
  })
})

describe('describeCheck', () => {
  it('классы: конструкции и чтение поля объекта', () => {
    expect([
      describeCheck({ kind: 'construct', construct: 'constructor' }),
      describeCheck({ kind: 'construct', construct: 'property' }),
      describeCheck({ kind: 'construct', construct: 'privateSet' }),
      describeCheck({ kind: 'construct', construct: 'this', min: 2 }),
      describeCheck({ kind: 'access', member: 'Hero.hp', min: 1 }),
      describeCheck({ kind: 'call', method: 'Hero.Heal', caller: 'Hero.Hero' }),
    ]).toEqual([
      'Используется конструктор',
      'Используется свойство',
      'Используется private set (менять можно только внутри класса)',
      'Используется this (поле своего объекта)',
      'Используется значение hp объекта Hero',
      'Метод Hero.Heal вызывается из Hero.Hero',
    ])
  })

  it('массив и список в set — как в C#: { 5, 9, 1 }', () => {
    expect(
      describeCheck({
        kind: 'output',
        expected: '',
        set: { scores: [5, 9, 1], names: ['Аня', 'Боб'], empty: [] },
      }),
    ).toBe(
      'Программа печатает то, что нужно, при scores = { 5, 9, 1 }, names = { «Аня», «Боб» }, empty = { }',
    )
  })

  it('подписи проверок простыми словами', () => {
    expect([
      describeCheck({ kind: 'output', expected: '' }),
      describeCheck({ kind: 'variable', name: 'hp', type: 'int' }),
      describeCheck({ kind: 'variable', name: 'hp' }),
      describeCheck({ kind: 'construct', construct: 'method' }),
      describeCheck({ kind: 'call', method: 'System.Math.Max' }),
      describeCheck({ kind: 'output', expected: '', set: { height: 5 } }),
      describeCheck({
        kind: 'output',
        expected: '',
        set: { name: 'Аня', speed: 2.5, alive: false },
      }),
      describeCheck({ kind: 'output', expected: '', set: {} }),
      describeCheck({ kind: 'construct', construct: 'break' }),
      describeCheck({ kind: 'construct', construct: 'continue' }),
      describeCheck({ kind: 'call', method: 'Program.GetStatus' }),
      describeCheck({ kind: 'call', method: 'Program.GetStatus', caller: 'Program.Report' }),
    ]).toEqual([
      'Программа печатает то, что нужно',
      'Есть переменная hp типа int',
      'Есть переменная hp',
      'Используется метод',
      'Вызывается Math.Max',
      'Программа печатает то, что нужно, при height = 5',
      'Программа печатает то, что нужно, при name = «Аня», speed = 2.5, alive = false',
      'Программа печатает то, что нужно',
      'Используется break (выход из цикла)',
      'Используется continue (переход к следующему шагу цикла)',
      'Вызывается GetStatus',
      'Метод GetStatus вызывается из Report',
    ])
  })
})
