type UserRole = 'admin' | 'user'

export const UserRole = {
    ADMIN: 'admin',
    USER: 'user',
} as const

export type LoginCredentials = {
    username: string
    password?: string
}

export type RegistrationCredentials = {
    name: string
    username: string
    email: string
    password?: string
}

export interface UpdateProfilePayload extends RegistrationCredentials {}

type Role = {
    id: number
    name: string
}

export type AuthenticatedUserData = {
    id: number
    name: string
    username: string
    email: string
    role_id: number
    role: Role
}
