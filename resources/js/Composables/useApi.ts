import axios from "axios";
import { ref } from "vue";

export function useApi() {
    const loading = ref(false);

    const apiClient = axios.create({
        baseURL: `${window.location.protocol}//${window.location.hostname}/api/`,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': document.head.querySelector("meta[name=\"csrf-token\"]"),
        },
        withCredentials: true,
        withXSRFToken: true,
    })

    // Request interceptor
    apiClient.interceptors.request.use(config => {
        loading.value = true;
        return config
    })

    // Response interceptor
    apiClient.interceptors.response.use(
        response => {
            loading.value = false;
            return response;
        },
        error => {
            loading.value = false;
            return Promise.reject(error);
        }
    )

    return {
        apiClient,
        loading
    }
}