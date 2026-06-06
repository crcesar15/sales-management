import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";
import type { PosSession, CashRegister } from "@/Types/pos";
import { PosError, PosPermissionError, PosNetworkError } from "@/Types/pos";
import { router } from "@inertiajs/vue3";
import axios from "axios";

export function usePosClient() {
  const { apiClient, loading } = useApi();

  async function getSession(): Promise<PosSession> {
    try {
      const { data } = await apiClient.get<PosSession>(route("api.v1.pos.session"));
      return data;
    } catch (error) {
      handleApiError(error);
      throw error;
    }
  }

  async function getRegisters(storeId?: number): Promise<CashRegister[]> {
    try {
      const { data } = await apiClient.get<CashRegister[]>(route("api.v1.pos.registers"), {
        params: { store_id: storeId },
      });
      return data;
    } catch (error) {
      handleApiError(error);
      throw error;
    }
  }

  async function selectRegister(registerId: number): Promise<PosSession> {
    try {
      const { data } = await apiClient.post<PosSession>(route("api.v1.pos.session.register"), {
        register_id: registerId,
      });
      return data;
    } catch (error) {
      handleApiError(error);
      throw error;
    }
  }

  async function openShift(registerId: number, openingBalance: number): Promise<PosSession> {
    try {
      const { data } = await apiClient.post<PosSession>(route("api.v1.pos.session.shift.open"), {
        register_id: registerId,
        opening_balance: openingBalance,
      });
      return data;
    } catch (error) {
      handleApiError(error);
      throw error;
    }
  }

  async function closeShift(shiftId: number, closingBalance?: number): Promise<PosSession> {
    try {
      const { data } = await apiClient.post<PosSession>(route("api.v1.pos.session.shift.close"), {
        shift_id: shiftId,
        closing_balance: closingBalance,
      });
      return data;
    } catch (error) {
      handleApiError(error);
      throw error;
    }
  }

  return {
    loading,
    getSession,
    getRegisters,
    selectRegister,
    openShift,
    closeShift,
  };
}

function handleApiError(error: unknown): void {
  if (axios.isAxiosError(error)) {
    const status = error.response?.status;

    if (status === 401) {
      // Session expired — redirect to login
      router.visit(route("login"));
      throw new PosError("Session expired", "UNAUTHORIZED");
    }

    if (status === 403) {
      throw new PosPermissionError(
        (error.response?.data as { message?: string })?.message || "You do not have permission for this action",
      );
    }

    if (status === 422) {
      // Validation errors — let the caller handle field-level errors
      throw error;
    }

    if (status && status >= 500) {
      throw new PosNetworkError("Something went wrong. Please try again.");
    }
  }

  // Network error or timeout
  throw new PosNetworkError("Unable to connect. Please check your network connection.");
}