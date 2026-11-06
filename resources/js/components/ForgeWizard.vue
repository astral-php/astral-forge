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
  destination: '../mon-application',
  recipe: 'blank',
})

const step = ref('config') // config | plan | result
const loading = ref(false)
const error = ref('')
const plan = ref(null)
const report = ref(null)
const confirmGenerate = ref(false)
const environment = ref(null)
const jobStatus = ref('')

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
  if (!res.ok && res.status !== 202) {
    throw new Error(data.error || data.message || `Erreur HTTP ${res.status}`)
  }
  return data
}

async function pollJob(jobId) {
  const maxAttempts = 120
  for (let i = 0; i < maxAttempts; i++) {
    jobStatus.value = i === 0
      ? 'Démarrage de la génération…'
      : `Génération en cours… (${i * 2}s)`
    await new Promise((r) => setTimeout(r, 2000))
    const res = await fetch(`/forge/api/jobs/${jobId}`, {
      headers: { Accept: 'application/json' },
    })
    const data = await res.json().catch(() => ({}))
    if (!res.ok) {
      throw new Error(data.error || `Suivi job HTTP ${res.status}`)
    }
    const job = data.job
    if (!job) {
      continue
    }
    if (job.status === 'done') {
      return job.report
    }
    if (job.status === 'failed') {
      throw new Error(job.error || 'Génération en arrière-plan échouée.')
    }
  }
  throw new Error('Délai dépassé — la génération continue peut‑être en arrière-plan. Réessayez plus tard.')
}

async function runPlan() {
  error.value = ''
  loading.value = true
  report.value = null
  jobStatus.value = ''
  try {
    const data = await api('/forge/api/plan', { ...form.value })
    plan.value = data.plan
    environment.value = data.environment || null
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
  jobStatus.value = ''
  try {
    const data = await api('/forge/api/generate', {
      ...form.value,
      confirm: true,
    })
    if (data.async && data.job?.id) {
      report.value = await pollJob(data.job.id)
    } else {
      report.value = data.report
    }
    step.value = 'result'
  } catch (e) {
    error.value = e.message || String(e)
  } finally {
    loading.value = false
    jobStatus.value = ''
  }
}

function backToConfig() {
  step.value = 'config'
  plan.value = null
  report.value = null
  error.value = ''
  jobStatus.value = ''
}
</script>

<template>
  <div class="space-y-5">
    <section class="forge-page-intro">
      <p class="eyebrow"><span class="eyebrow-mark"></span> ASTRAL FORGE · ASSISTANT DE GÉNÉRATION · 0.4</p>
      <h1>Nouveau projet</h1>
      <p>Configurez votre application, vérifiez le plan, puis lancez une génération greenfield.</p>
    </section>

    <div v-if="error" class="rounded-xl border px-4 py-3 text-sm" style="background: var(--accent-soft); border-color: #f0c2be; color: #8a1f1a" role="alert">
      {{ error }}
    </div>
    <div
      v-if="jobStatus"
      class="rounded-xl border px-4 py-3 text-sm"
      style="background: #fff8e8; border-color: #e8d4a8; color: #6a4b00"
      role="status"
    >
      {{ jobStatus }}
    </div>

    <section v-show="step === 'config'" class="forge-card space-y-5">
      <div>
        <label class="block text-sm font-semibold mb-1" for="project_name">Nom (kebab-case)</label>
        <input id="project_name" v-model="form.project_name" class="forge-input" required />
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1" for="destination">Destination</label>
        <input id="destination" v-model="form.destination" class="forge-input" placeholder="../demo-forge-app" required />
      </div>
      <div>
        <label class="block text-sm font-semibold mb-1" for="recipe">Recette</label>
        <select id="recipe" v-model="form.recipe" class="forge-input">
          <option v-for="r in recipes" :key="r.id" :value="r.id">{{ r.label }}</option>
        </select>
      </div>
      <button type="button" class="forge-btn-primary" :disabled="!canPlan || loading" @click="runPlan">
        {{ loading ? 'Calcul…' : 'Voir le plan' }}
      </button>
    </section>

    <section v-show="step === 'plan'" class="forge-card space-y-4">
      <h2 class="m-0" style="font-size:20px;font-weight:800">Plan</h2>
      <p v-if="plan" class="text-sm m-0" style="color: var(--muted)">→ {{ plan.destination }}</p>
      <ol v-if="plan" class="m-0 pl-5 text-sm leading-relaxed space-y-1">
        <li v-for="(op, i) in plan.operations" :key="i">
          <code class="text-xs">{{ op.type }}</code>
          — {{ op.description || op.path }}
        </li>
      </ol>
      <p
        v-if="environment && environment.web_generate_mode === 'async'"
        class="text-sm m-0 rounded-xl border px-4 py-3"
        style="background: #f3f7ff; border-color: #c5d4f0; color: #1e3a6e"
      >
        {{ environment.web_generate_hint || 'La génération se fera en arrière-plan depuis cette interface.' }}
      </p>
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
      <h2 class="m-0" style="font-size:20px;font-weight:800">Rapport</h2>
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
      <ul v-if="report && report.errors && report.errors.length" class="m-0 pl-5 text-sm" style="color:#8a1f1a">
        <li v-for="(err, i) in report.errors" :key="'e'+i">{{ err }}</li>
      </ul>
      <button type="button" class="forge-btn-ghost" @click="backToConfig">Nouveau projet</button>
    </section>
  </div>
</template>
