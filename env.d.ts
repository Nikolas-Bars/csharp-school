/// <reference types="vite/client" />

interface ImportMetaEnv {
  /** Адрес бэкенда, например http://localhost:8290; не задан — API на том же домене (/api) */
  readonly VITE_API_URL?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
