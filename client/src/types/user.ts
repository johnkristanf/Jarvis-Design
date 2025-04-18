
type UserRole = 'admin' | 'user';

export const UserRole = {
    ADMIN: 'admin',
    USER: 'user',
} as const;


export type LoginCredentials = {
    username: string,
    password?: string
}

export type RegistrationCredentials = {
    name: string,
    username: string,
    password?: string
}


type Role = {
    id: number,
    name: string
}

export type AuthenticatedUserData = {
    id: number
    name: string,
    username: string,
    role_id: number,
    role: Role
}
