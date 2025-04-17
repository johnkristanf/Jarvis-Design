import type { ProceedPaymentData, ProceedPaymentResponseData } from "@/types/payment";
import { apiService } from "../axios";
import type { AxiosResponse } from "axios";
import type { OrderTypes } from "@/types/order";

export const generateQrCode = async (designID: number, totalPrice: number, orderType: OrderTypes, quantity: number, color: number, size: number): Promise<ProceedPaymentResponseData> =>  {
    const convertedPesosToCents = totalPrice * 100;

    const respData = await apiService.post<ProceedPaymentResponseData>('/api/paymongo/create-qr-source', {
        design_id: designID,
        total_price: convertedPesosToCents,
        order_type: orderType,
        quantity,
        color,
        size,
    });

    return respData;
}