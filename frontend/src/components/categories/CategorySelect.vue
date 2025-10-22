<template>
  <div class="relative" ref="root">
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">{{ label }}</label>
    <button
      type="button"
      class="w-full h-10 px-3 py-2 rounded-lg border border-gray-300 bg-white text-left text-gray-900 shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400 flex items-center justify-between"
      @click="toggle"
    >
      <span class="flex items-center gap-2 truncate">
        <span v-if="selectedOption" class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-gray-100 text-gray-700">
          <component :is="iconComponent(selectedOption.icon)" class="h-4 w-4" />
        </span>
        <span class="truncate">{{ selectedOption?.label || placeholder }}</span>
      </span>
      <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <transition name="fade">
      <div v-if="open" class="absolute z-50 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg">
        <div class="p-2">
          <input v-model="query" type="text" placeholder="Buscar..." class="w-full h-9 px-3 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-400" />
        </div>
        <ul class="max-h-56 overflow-auto py-1">
          <li v-if="filtered.length === 0" class="px-3 py-2 text-sm text-gray-500">Nada encontrado</li>
          <li v-for="opt in filtered" :key="opt.value">
            <button type="button" class="w-full px-3 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2" @click="select(opt.value)">
              <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-gray-100 text-gray-700">
                <component :is="iconComponent(opt.icon)" class="h-4 w-4" />
              </span>
              <span class="truncate">{{ opt.label }}</span>
            </button>
          </li>
        </ul>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from 'vue'
import {
  TagIcon,
  ShoppingCartIcon,
  HomeIcon,
  CreditCardIcon,
  BanknotesIcon,
  CurrencyDollarIcon,
  BoltIcon,
  GiftIcon,
  AcademicCapIcon,
  GlobeAltIcon,
  HeartIcon,
  TruckIcon,
  WrenchScrewdriverIcon,
  BriefcaseIcon,
  ComputerDesktopIcon,
  PhoneIcon,
  FilmIcon,
  BeakerIcon,
  MusicalNoteIcon,
  FireIcon,
  SunIcon,
  CakeIcon,
  BookOpenIcon,
  BuildingStorefrontIcon,
  BugAntIcon,
  MapPinIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  modelValue: { type: [Number, String, null], default: null },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Selecione...' },
  label: { type: String, default: '' }
})
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const query = ref('')
const root = ref(null)

const selectedOption = computed(() => props.options.find(o => o.value === props.modelValue))

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return props.options
  return props.options.filter(o => o.label.toLowerCase().includes(q))
})

function toggle() { open.value = !open.value }
function close() { open.value = false }
function select(val) { emit('update:modelValue', val); close() }

// simple click outside
function onClick(e) {
  if (!root.value) return
  if (!root.value.contains(e.target)) close()
}

onMounted(() => document.addEventListener('click', onClick))
onBeforeUnmount(() => document.removeEventListener('click', onClick))

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
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
