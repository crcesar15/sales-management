export interface SettingBase {
  // columns
  key: string;
  value: string;
  name: string;
  group: string;
}

export interface SettingResponse extends SettingBase {
  id: number;
  created_at: string;
  updated_at: string;
}

export interface SettingPayload extends SettingBase {}

export interface SettingGrouped {
  [group: string]: Record<string, string>;
}
