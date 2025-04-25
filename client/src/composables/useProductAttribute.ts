import { getAllColors, getAllSizes } from '@/api/get/designs'
import type { Colors, Sizes } from '@/types/design'
import { ref, onMounted } from 'vue'

export function useProductAttributes() {
    const colors = ref<Colors[]>([])
    const sizes = ref<Sizes[]>([])

    const loadingColors = ref<boolean>(false)
    const loadingSizes = ref<boolean>(false)

    const fetchColors = async () => {
        loadingColors.value = true

        try {
            const res = await getAllColors()
            colors.value = res
        } catch (err: any) {
            console.error('Error fetching colors:', err)
        } finally {
            loadingColors.value = false
        }
    }

    const fetchSizes = async () => {
        loadingSizes.value = true

        try {
            const res = await getAllSizes()
            sizes.value = res
        } catch (err: any) {
            console.error('Error fetching sizes:', err)
        } finally {
            loadingSizes.value = false
        }
    }

    onMounted(() => {
        fetchColors()
        fetchSizes()
    })

    return {
        colors,
        sizes,
        loadingColors,
        loadingSizes,
    }
}
