import type { ProceedPaymentData, ProceedPaymentResponseData } from "@/types/payment";
import { apiService } from "../axios";
import type { AxiosResponse } from "axios";

export const generateQrCode = async (amount: number): Promise<ProceedPaymentResponseData> =>  {
    const convertedPesosToCents = amount * 100;

    const respData = await apiService.post<ProceedPaymentResponseData>('/api/paymongo/create-qr-source', {
        price: convertedPesosToCents,
    });

    return respData;
}