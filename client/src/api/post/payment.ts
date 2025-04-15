import type { ProceedPaymentData, ProceedPaymentResponseData } from "@/types/payment";
import { apiService } from "../axios";
import type { AxiosResponse } from "axios";

export const generateQrCode = async (amount: number): Promise<ProceedPaymentResponseData | undefined> =>  {
    try {

        const convertedPesosToCents = amount * 100;

        const response: ProceedPaymentResponseData = await apiService.post('/api/paymongo/create-qr-source', {
            amount: convertedPesosToCents,
        });

        return response;
      
    } catch (error) {
      console.error('Error generating QR code:', error);
    }

}