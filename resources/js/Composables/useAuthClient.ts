import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";

export function useAuthClient() {
    const { apiClient}  = useApi();

    interface loginPayload {
        username: string;
        password: string;
        remember: boolean;
    }

    const loginApi = async (loginPayload:loginPayload) => {
        return await apiClient.post(route('login.post'), loginPayload);
    }

    const requestResetPasswordApi = async (payload: { email: string }) => {
        return await apiClient.post(route('password.email'), payload);
    }

    interface resetPasswordPayload {
        email: string;
        password: string;
        password_confirmation: string;
        token: string;
    }

    const resetPasswordApi = async (payload: resetPasswordPayload) => {
        return await apiClient.post(route('password.reset.update'), payload);
    }

    return {
        loginApi,
        resetPasswordApi,
        requestResetPasswordApi
    }
}