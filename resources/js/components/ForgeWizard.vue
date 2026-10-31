<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  csrfToken: { type: String, required: true },
})

const recipes = [
  { id: 'blank', label: 'blank — starter seul' },
  { id: 'blog-lite', label: 'blog-lite — + Article CRUD' },
  { id: 'api-json', label: 'api-json — /api/v1 + Cors' },
]

const form = ref({
  project_name: 'mon-application',
  destination: '',
  recipe: 'blank',
})

const step = ref('config') // config | plan | result
const loading = ref(false)
const error = ref('')
const plan = ref(null)
const report = ref(null)
const confirmGenerate = ref(false)

const canPlan = computed(() => {
  return form.value.project_name.trim() !== '' && form.value.destination.trim() !== ''
})

async function api(path, body) {
  const res = await fetch(path, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-CSRF-Token': props.csrfToken,
    },
    body: JSON.stringify(body),
  })
  const data = await res.json().catch(() => ({}))
  if (!res.ok) {
    throw new Error(data.error || data.message || `Erreur HTTP ${res.status}`)
  }
  return data
}

async function runPlan() {
  error.value = ''
  loading.value = true
  report.value = null
  try {
    const data = await api('/forge/api/plan', { ...form.value })
    plan.value = data.plan
    step.value = 'plan'
    confirmGenerate.value = false
  } catch (e) {
    error.value = e.message || String(e)
  } finally {
    loading.value = false
  }
}

async function runGenerate() {
  if (!confirmGenerate.value) {
    error.value = 'Cochez la confirmation avant de générer.'
    return
  }
  error.value = ''
  loading.value = true
  try {
    const data = await api('/forge/api/generate', {
      ...form.value,
      confirm: true,
    })
    report.value = data.report
    step.value = 'result'
  } catch (e) {
    error.value = e.message || String(e)
  } finally {
    loading.value = false
  }
}

function backToConfig() {
  step.value = 'config'
  plan.value = null
  report.value = null
  error.value = ''
}
</script>

<template>
  <div class="max-w-xl space-y-5">
    <header>
      <p class="text-xs uppercase tracking-widest mb-2" style="color: var(--muted)">
        Préversion 0.3 · Vue + Vite
      </p>
      <h1 class="font-serif text-3xl font-bold m-0 mb-2" style="font-family: Fraunces, Georgia, serif">
        Nouveau projet Astral
      </h1>
      <p class="text-sm leading-relaxed m-0" style="color: var(--muted)">
        Config → plan (dry-run) → confirmation → génération greenfield.
      </p>
    </header>

    <div v-if="error" class="rounded-xl border px-4 py-3 text-sm" style="background: var(--accent-soft); border-color: #f0c2be; color: #8a1f1a">
      {{ error }}
    </div>

    <section v-show="step === 'config'" class="forge-card space-y-4">
      <div>
        <label class="block text-sm font-medium mb-1" for="project_name">Nom (kebab-case)</label>
        <input id="project_name" v-model="form.project_name" class="forge-input" required />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1" for="destination">Destination</label>
        <input id="destination" v-model="form.destination" class="forge-input" placeholder="../demo-forge-app" required />
      </div>
      <div>
        <label class="block text-sm font-medium mb-1" for="recipe">Recette</label>
        <select id="recipe" v-model="form.recipe" class="forge-input">
          <option v-for="r in recipes" :key="r.id" :value="r.id">{{ r.label }}</option>
        </select>
      </div>
      <button type="button" class="forge-btn-primary" :disabled="!canPlan || loading" @click="runPlan">
        {{ loading ? 'Calcul…' : 'Voir le plan' }}
      </button>
    </section>

    <section v-show="step === 'plan'" class="forge-card space-y-4">
      <h2 class="font-serif text-xl font-bold m-0" style="font-family: Fraunces, Georgia, serif">Plan</h2>
      <p v-if="plan" class="text-sm m-0" style="color: var(--muted)">→ {{ plan.destination }}</p>
      <ol v-if="plan" class="m-0 pl-5 text-sm leading-relaxed space-y-1">
        <li v-for="(op, i) in plan.operations" :key="i">
          <code class="text-xs">{{ op.type }}</code>
          — {{ op.description || op.path }}
        </li>
      </ol>
      <label class="flex gap-2 items-start text-sm">
        <input v-model="confirmGenerate" type="checkbox" class="mt-1" />
        <span>Je confirme la génération greenfield (destination vide).</span>
      </label>
      <div class="flex flex-wrap gap-3">
        <button type="button" class="forge-btn-primary" :disabled="loading || !confirmGenerate" @click="runGenerate">
          {{ loading ? 'Génération…' : 'Générer' }}
        </button>
        <button type="button" class="forge-btn-ghost" :disabled="loading" @click="backToConfig">Modifier</button>
      </div>
    </section>

    <section v-show="step === 'result'" class="forge-card space-y-4">
      <h2 class="font-serif text-xl font-bold m-0" style="font-family: Fraunces, Georgia, serif">Rapport</h2>
      <p v-if="report" class="text-sm m-0">
        Statut :
        <strong>{{ report.success ? 'OK' : 'Échec' }}</strong>
        <span v-if="report.destination"> — {{ report.destination }}</span>
      </p>
      <ul v-if="report" class="m-0 pl-5 text-sm leading-relaxed space-y-1">
        <li v-for="(entry, i) in report.entries" :key="i">
          [{{ entry.status }}] {{ entry.operation }} — {{ entry.message }}
        </li>
      </ul>
      <ul v-if="report && report.errors && report.errors.length" class="m-0 pl-5 text-sm text-red-800">
        <li v-for="(err, i) in report.errors" :key="'e'+i">{{ err }}</li>
      </ul>
      <button type="button" class="forge-btn-ghost" @click="backToConfig">Nouveau projet</button>
    </section>
  </div>
</template>
