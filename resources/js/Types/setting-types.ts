export interface Setting {
  // columns
  id: number
  key: string
  value: string
  name: string
  group: string
  created_at: string | null
  updated_at: string | null
}

interface SingleSettings {
  [key: string]: string;
}

export interface SettingGrouped {
  [key: string]: SingleSettings;
}

let a = {
  settings: {
    'users':
      {
        'name': 'app',
        'env': 'test'
      }
  }
}