export interface ActivityLogProperties {
  old: Record<string, unknown> | null;
  attributes: Record<string, unknown> | null;
}

export interface ActivityLogCauser {
  id: number;
  full_name: string;
}

export interface ActivityLog {
  id: number;
  log_name: string;
  description: string;
  event: string;
  subject_type: string | null;
  subject_id: number | null;
  properties: ActivityLogProperties;
  causer: ActivityLogCauser | null;
  created_at: string;
}
