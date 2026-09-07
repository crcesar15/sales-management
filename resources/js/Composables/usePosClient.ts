import { useApi } from "@composables/useApi";
import { route } from "ziggy-js";
import type {
  CashRegister,
  PosCloseShiftPayload,
  PosCloseShiftResponse,
  PosMovementPayload,
  PosSession,
  ShiftReconciliation,
} from "@/Types/pos";
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
      const { data } = await apiClient.get<{ data: CashRegister[] }>(route("api.v1.pos.registers"), {
        params: { store_id: storeId },
      });
      return data.data;
    } catch (error) {
      handleApiError(error);
      throw error;
    }
  }

  async function openShift(registerId: number, openingBalance: number, openingNotes?: string | null): Promise<PosSession> {
    try {
      const { data } = await apiClient.post<PosSession>(route("api.v1.pos.session.shift.open"), {
        register_id: registerId,
        opening_balance: openingBalance,
        opening_notes: openingNotes,
      });
      return data;
    } catch (error) {
      handleApiError(error);
      throw error;
    }
  }

  async function getClosingSummary(): Promise<ShiftReconciliation> {
    try {
      const { data } = await apiClient.get<{ data: ShiftReconciliation }>(route("api.v1.pos.session.shift.closing-summary"));
      return data.data;
    } catch (error) {
      handleApiError(error);
      throw error;
    }
  }

  async function closeShift(payload: PosCloseShiftPayload): Promise<PosCloseShiftResponse> {
    try {
      const { data } = await apiClient.patch<PosCloseShiftResponse>(route("api.v1.pos.session.shift.close"), payload);
      return data;
    } catch (error) {
      handleApiError(error);
      throw error;
    }
  }

  async function addMovement(payload: PosMovementPayload): Promise<PosSession> {
    try {
      const { data } = await apiClient.post<PosSession>(route("api.v1.pos.session.shift.movements.store"), payload);
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
    openShift,
    getClosingSummary,
    closeShift,
    addMovement,
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
      throw new PosPermissionError((error.response?.data as { message?: string })?.message || "You do not have permission for this action");
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
