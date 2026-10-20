import { RunnerClient } from '@/runner/client'

let client: RunnerClient | undefined

/** Runner один на вкладку: .NET загружается один раз и живёт, пока открыта страница */
export function useRunner(): RunnerClient {
  client ??= new RunnerClient()
  return client
}
