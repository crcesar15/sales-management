import {Permission} from "./permission-types"

interface RoleBase {
    name: string,
}

export interface RoleResponse extends RoleBase {
    id: number,
    created_at: string,
    updated_at: string,
    permissions: Permission[]
}

export interface RolePayload extends RoleBase {
    permissions: string[]
}