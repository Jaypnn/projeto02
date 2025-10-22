<template>
  <div>
    <div class="mb-3 flex items-center gap-2">
      <input
        v-model="query"
        type="text"
        placeholder="Buscar ícone..."
        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
      />
      <button
        v-if="modelValue"
        class="px-3 py-2 text-sm rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50"
        @click="$emit('update:modelValue', null)"
      >
        Limpar
      </button>
    </div>

    <div class="grid grid-cols-8 gap-2 max-h-48 overflow-auto pr-1">
      <button
        v-for="item in filteredIcons"
        :key="item.key"
        type="button"
        class="flex items-center justify-center aspect-square rounded-md border text-gray-700 hover:bg-gray-50"
        :class="modelValue === item.key ? 'border-blue-500 ring-2 ring-blue-200 bg-blue-50' : 'border-gray-200'"
        @click="$emit('update:modelValue', item.key)"
        :title="item.label"
      >
        <component :is="item.component" class="w-5 h-5" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
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
  modelValue: { type: String, default: null }
})

const icons = [
  { key: 'tag', label: 'Tag', component: TagIcon },
  { key: 'shopping', label: 'Compras', component: ShoppingCartIcon },
  { key: 'home', label: 'Casa', component: HomeIcon },
  { key: 'credit-card', label: 'Cartão', component: CreditCardIcon },
  { key: 'cash', label: 'Dinheiro', component: BanknotesIcon },
  { key: 'salary', label: 'Salário', component: CurrencyDollarIcon },
  { key: 'energy', label: 'Energia', component: BoltIcon },
  { key: 'gift', label: 'Presente', component: GiftIcon },
  { key: 'education', label: 'Educação', component: AcademicCapIcon },
  { key: 'internet', label: 'Internet', component: GlobeAltIcon },
  { key: 'health', label: 'Saúde', component: HeartIcon },
  { key: 'transport', label: 'Transporte', component: TruckIcon },
  { key: 'repairs', label: 'Manutenção', component: WrenchScrewdriverIcon },
  { key: 'work', label: 'Trabalho', component: BriefcaseIcon },
  { key: 'electronics', label: 'Eletrônicos', component: ComputerDesktopIcon },
  { key: 'phone', label: 'Telefone', component: PhoneIcon },
  { key: 'entertainment', label: 'Entretenimento', component: FilmIcon },
  { key: 'lab', label: 'Laboratório', component: BeakerIcon },
  { key: 'music', label: 'Música', component: MusicalNoteIcon },
  { key: 'gas', label: 'Gás', component: FireIcon },
  { key: 'sun', label: 'Lazer', component: SunIcon },
  { key: 'birthday', label: 'Aniversário', component: CakeIcon },
  { key: 'books', label: 'Livros', component: BookOpenIcon },
  { key: 'market', label: 'Mercado', component: BuildingStorefrontIcon },
  { key: 'pets', label: 'Pets', component: BugAntIcon },
  { key: 'travel', label: 'Viagem', component: MapPinIcon },
]

const query = ref('')

const filteredIcons = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return icons
  return icons.filter(i =>
    i.key.includes(q) || i.label.toLowerCase().includes(q)
  )
})
</script>
