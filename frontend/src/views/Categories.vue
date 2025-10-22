<template>
  <div class="space-y-6">
    <!-- header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Categorias</h1>
        <p class="mt-1 text-sm text-gray-500">
          Organize suas transações por categorias
        </p>
      </div>
      <button @click="openCreate" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
        <PlusIcon class="w-4 h-4 mr-2" />
        Nova Categoria
      </button>
    </div>

    <!-- search -->
    <div class="flex items-center gap-2">
      <div class="ml-0 sm:ml-auto relative">
        <input v-model="search" type="text" placeholder="Buscar..." class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-56" />
      </div>
    </div>

    <!-- empty state -->
    <div v-if="!loading && filtered.length === 0" class="bg-white shadow rounded-lg p-6 text-center text-gray-500">
      <TagIcon class="w-12 h-12 mx-auto mb-4 text-gray-300" />
      <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma categoria</h3>
      <p>Crie sua primeira categoria para organizar suas transações.</p>
    </div>

    <!-- grid -->
    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <div v-for="cat in filtered" :key="cat.id" class="group bg-white rounded-lg border border-gray-200 p-4 hover:shadow-sm">
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-md bg-gray-100 flex items-center justify-center text-gray-700">
              <component :is="iconComponent(cat.icon)" class="w-5 h-5" />
            </div>
            <div>
              <div class="font-medium text-gray-900">{{ cat.name }}</div>
            </div>
          </div>
          <div class="opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
            <button class="px-2 py-1 text-xs rounded-md border border-gray-300 hover:bg-gray-50" @click="openEdit(cat)">Editar</button>
            <button class="px-2 py-1 text-xs rounded-md border border-rose-200 text-rose-700 hover:bg-rose-50" @click="askDelete(cat)">Excluir</button>
          </div>
        </div>
      </div>
    </div>

    <!-- modals -->
    <CategoryModal v-model="showModal" :title="modalTitle" :confirm-text="modalConfirm" :category="current" @confirm="onSave" />
    <ConfirmModal v-model="showConfirm" title="Excluir categoria" :message="confirmMessage" confirm-text="Excluir" cancel-text="Cancelar" @confirm="onDelete" @cancel="showConfirm=false" />
  </div>
  
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useStore } from 'vuex'
import { PlusIcon, TagIcon, ShoppingCartIcon, HomeIcon, CreditCardIcon, BanknotesIcon, CurrencyDollarIcon, BoltIcon, GiftIcon, AcademicCapIcon, GlobeAltIcon, HeartIcon, TruckIcon, WrenchScrewdriverIcon, BriefcaseIcon, ComputerDesktopIcon, PhoneIcon, FilmIcon, BeakerIcon, MusicalNoteIcon, FireIcon, SunIcon, CakeIcon, BookOpenIcon, BuildingStorefrontIcon, BugAntIcon, MapPinIcon } from '@heroicons/vue/24/outline'
import CategoryModal from '@/components/categories/CategoryModal.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'

const store = useStore()

const loading = computed(() => store.getters['categories/categoriesLoading'])
const items = computed(() => store.getters['categories/allCategories'])

const search = ref('')

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase()
  return items.value
    .filter(c => !q || c.name.toLowerCase().includes(q))
})

onMounted(() => {
  store.dispatch('categories/fetchCategories')
})

// no segmented filter; category doesn't carry type anymore

// icon mapping
const iconMap = {
  tag: TagIcon,
  shopping: ShoppingCartIcon,
  home: HomeIcon,
  'credit-card': CreditCardIcon,
  cash: BanknotesIcon,
  salary: CurrencyDollarIcon,
  energy: BoltIcon,
  gift: GiftIcon,
  education: AcademicCapIcon,
  internet: GlobeAltIcon,
  health: HeartIcon,
  transport: TruckIcon,
  repairs: WrenchScrewdriverIcon,
  work: BriefcaseIcon,
  electronics: ComputerDesktopIcon,
  phone: PhoneIcon,
  entertainment: FilmIcon,
  lab: BeakerIcon,
  music: MusicalNoteIcon,
  gas: FireIcon,
  sun: SunIcon,
  birthday: CakeIcon,
  books: BookOpenIcon,
  market: BuildingStorefrontIcon,
  pets: BugAntIcon,
  travel: MapPinIcon,
}

const iconComponent = (key) => iconMap[key] || TagIcon

// modal state
const showModal = ref(false)
const showConfirm = ref(false)
const current = ref(null)
const modalTitle = ref('Nova Categoria')
const modalConfirm = ref('Criar')
const confirmMessage = ref('Tem certeza que deseja excluir esta categoria? Esta ação não pode ser desfeita.')

function openCreate() {
  current.value = null
  modalTitle.value = 'Nova Categoria'
  modalConfirm.value = 'Criar'
  showModal.value = true
}

function openEdit(cat) {
  current.value = cat
  modalTitle.value = 'Editar Categoria'
  modalConfirm.value = 'Salvar'
  showModal.value = true
}

async function onSave(payload) {
  try {
    if (current.value) {
      await store.dispatch('categories/updateCategory', { id: current.value.id, data: payload })
    } else {
      await store.dispatch('categories/createCategory', payload)
    }
    showModal.value = false
  } catch (e) {
    // TODO: toast de erro
    console.error(e)
  }
}

function askDelete(cat) {
  current.value = cat
  showConfirm.value = true
}

async function onDelete() {
  try {
    await store.dispatch('categories/deleteCategory', current.value.id)
    showConfirm.value = false
  } catch (e) {
    console.error(e)
  }
}
</script>

<style scoped>
</style>