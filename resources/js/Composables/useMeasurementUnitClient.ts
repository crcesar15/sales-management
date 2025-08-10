import { MeasurementUnit } from "@app-types/measurement-unit-types";
import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";
import { AxiosResponse } from "axios";

export function useMeasurementUnitClient() {
    const { apiClient, loading }  = useApi();

    const fetchMeasurementUnitsApi = async <T = any>(queryParameters?: string):Promise<AxiosResponse<T>>  => {
        let url:string = route('api.measurement-units');

        if (queryParameters) {
            url += `?${queryParameters}`
        }

        return await apiClient.get(url)
    }

    const showMeasurementUnitApi = async (id: number) => {
        return await apiClient.get(route('api.measurement-units.show', id));
    }

    const storeMeasurementUnitApi = async (measurementUnit:Pick<MeasurementUnit, 'name' | 'abbreviation'>) => {
        return await apiClient.post(route('api.measurement-units.store'), measurementUnit);
    }

    const updateMeasurementUnitApi = async (id:number, measurementUnit:MeasurementUnit) => {
        return await apiClient.put(route('api.measurement-units.update', id), measurementUnit);
    }

    const destroyMeasurementUnitApi = async (id:number) => {
        return await apiClient.delete(route('api.measurement-units.destroy', id));
    }

    return {
        loading,
        fetchMeasurementUnitsApi,
        showMeasurementUnitApi,
        storeMeasurementUnitApi,
        updateMeasurementUnitApi,
        destroyMeasurementUnitApi,
    }
}