import type { DesignGenerate } from "@/types/design";
import axios, { type AxiosResponse } from "axios";

export async function generateImageDesign(data: DesignGenerate): Promise<AxiosResponse> {
        return axios.post(`${import.meta.env.VITE_AI_API_URL}/generate/design`, data, {
            withCredentials: true,
            withXSRFToken: true,
            headers: {
                Accept: 'application/json'
            }
        });

}