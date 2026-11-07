<script setup>
import { computed, onMounted, ref } from 'vue'

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
const envLoading = ref(true)
/** @type {import('vue').Ref<null | { label: string, elapsedSec: number, mode: 'async' | 'sync' }>} */
const jobProgress = ref(null)

const canPlan = computed(() => {
  return form.value.project_name.trim() !== '' && form.value.destination.trim() !== ''
})

const envOk = computed(() => environment.value?.ok === true)

const envErrors = computed(() => {
  const errors = environment.value?.errors
  return Array.isArray(errors) ? errors : []
})

const canGenerate = computed(() => confirmGenerate.value && envOk.value && !loading.value)

async function loadEnvironment() {
  envLoading.value = true
  try {
    const res = await fetch('/forge/api/environment', { headers: { Accept: 'application/json' } })
    const data = await res.json().catch(() => ({}))
    environment.value = data.environment || data || null
    if (environment.value && typeof environment.value.ok !== 'boolean') {
      environment.value.ok = data.ok === true
    }
  } catch (e) {
    environment.value = {
      ok: false,
      errors: ['Impossible de vérifier PHP / Composer : ' + (e.message || String(e))],
    }
  } finally {
    envLoading.value = false
  }
}

onMounted(() => {
  loadEnvironment()
})

const progressTimeLabel = computed(() => {
  const sec = jobProgress.value?.elapsedSec ?? 0
  if (sec < 60) {
    return `${sec}s`
  }
  const m = Math.floor(sec / 60)
  const s = sec % 60
  return `${m}:${String(s).padStart(2, '0')}`
})

const reportOk = computed(() => report.value?.success === true)

const reportSteps = computed(() => {
  const entries = report.value?.entries
  if (!Array.isArray(entries)) {
    return []
  }
  return entries.map((entry) => ({
    ...entry,
    label: operationLabel(entry.operation),
    tone: entryStatusTone(entry.status),
  }))
})

const destinationShort = computed(() => {
  const dest = report.value?.destination || plan.value?.destination || ''
  if (!dest) {
    return ''
  }
  const normalized = String(dest).replace(/\\/g, '/')
  const parts = normalized.split('/').filter(Boolean)
  return parts.length > 2 ? parts.slice(-2).join('/') : normalized
})

function operationLabel(type) {
  const map = {
    ensure_empty_dir: 'Préparer le dossier',
    composer_create_project: 'Installer le starter Astral',
    write_crud_module: 'Ajouter le module CRUD',
    write_api_bootstrap: 'Configurer l’API JSON',
    note: 'Note',
  }
  return map[type] || String(type || 'Étape').replace(/_/g, ' ')
}

function entryStatusTone(status) {
  const s = String(status || '').toLowerCase()
  if (s === 'ok' || s === 'done' || s === 'success') {
    return 'ok'
  }
  if (s === 'planned' || s === 'skip' || s === 'skipped') {
    return 'muted'
  }
  if (s === 'error' || s === 'failed' || s === 'fail') {
    return 'err'
  }
  return 'muted'
}

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

function progressLabelForElapsed(elapsedSec) {
  if (elapsedSec < 4) {
    return 'Démarrage de la génération'
  }
  if (elapsedSec < 18) {
    return 'Installation du starter Astral'
  }
  if (elapsedSec < 36) {
    return 'Assemblage du projet'
  }
  return 'Finalisation en cours'
}

async function pollJob(jobId) {
  const maxAttempts = 120
  const started = Date.now()
  jobProgress.value = {
    label: 'Démarrage de la génération',
    elapsedSec: 0,
    mode: 'async',
  }

  for (let i = 0; i < maxAttempts; i++) {
    await new Promise((r) => setTimeout(r, 2000))
    const elapsedSec = Math.round((Date.now() - started) / 1000)
    jobProgress.value = {
      label: progressLabelForElapsed(elapsedSec),
      elapsedSec,
      mode: 'async',
    }

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
  jobProgress.value = null
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
  if (!envOk.value) {
    error.value = 'Configuration requise : corrigez PHP / Composer avant de générer.'
    return
  }
  error.value = ''
  loading.value = true
  jobProgress.value = {
    label: 'Génération en cours',
    elapsedSec: 0,
    mode: 'sync',
  }
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
    jobProgress.value = null
  }
}

function backToConfig() {
  step.value = 'config'
  plan.value = null
  report.value = null
  error.value = ''
  jobProgress.value = null
}
</script>

<template>
  <div class="space-y-5">
    <section class="forge-page-intro">
      <p class="eyebrow"><span class="eyebrow-mark"></span> ASTRAL FORGE · ASSISTANT DE GÉNÉRATION · 0.5</p>
      <h1>Nouveau projet</h1>
      <p>Configurez votre application, vérifiez le plan, puis lancez une génération greenfield. <a href="/forge" style="color:var(--accent);font-weight:600">Mes projets</a></p>
    </section>

    <div v-if="error" class="rounded-xl border px-4 py-3 text-sm" style="background: var(--accent-soft); border-color: #f0c2be; color: #8a1f1a" role="alert">
      {{ error }}
    </div>
    <div v-if="jobProgress" class="forge-progress" role="status" aria-live="polite">
      <div class="forge-progress-main">
        <span class="forge-progress-spinner" aria-hidden="true"></span>
        <div class="forge-progress-copy">
          <strong>{{ jobProgress.label }}</strong>
          <small v-if="jobProgress.mode === 'async'">Composer tourne en arrière-plan — vous pouvez laisser cet onglet ouvert.</small>
          <small v-else>Patientez quelques secondes…</small>
        </div>
        <span class="forge-progress-timer" :title="'Écoulé : ' + progressTimeLabel">
          <span class="forge-progress-timer-dot" aria-hidden="true"></span>
          {{ progressTimeLabel }}
        </span>
      </div>
      <div class="forge-progress-bar" aria-hidden="true">
        <span class="forge-progress-bar-fill"></span>
      </div>
    </div>

    <section v-show="step === 'config'" class="forge-card space-y-5">
      <div
        class="forge-env-banner"
        :class="envLoading ? '' : envOk ? 'is-ok' : 'is-err'"
        role="status"
      >
        <div class="forge-env-banner-head">
          <strong>
            <template v-if="envLoading">Vérification de l’environnement…</template>
            <template v-else-if="envOk">Configuration OK</template>
            <template v-else>Configuration requise</template>
          </strong>
          <span class="tag" :class="envLoading ? 'tag-neutral' : envOk ? 'tag-ok' : 'tag-err'">
            {{ envLoading ? '…' : envOk ? 'OK' : 'à corriger' }}
          </span>
        </div>
        <p v-if="!envLoading && envOk">
          PHP et Composer détectés — vous pouvez planifier puis générer.
        </p>
        <template v-else-if="!envLoading && !envOk">
          <p>Corrigez l’environnement avant de générer (voir <code>.env</code> : <code>FORGE_PHP_BIN</code>, <code>FORGE_COMPOSER_BIN</code>).</p>
          <ul v-if="envErrors.length">
            <li v-for="(err, i) in envErrors" :key="'env' + i">{{ err }}</li>
          </ul>
        </template>
        <p v-if="environment && (environment.php || environment.composer)" class="forge-env-meta">
          <template v-if="environment.php">PHP : {{ environment.php }}</template>
          <template v-if="environment.php && environment.composer"> · </template>
          <template v-if="environment.composer">Composer : {{ environment.composer }}</template>
        </p>
        <div v-if="!envLoading && !envOk" class="mt-3">
          <button type="button" class="forge-btn-ghost" :disabled="loading" @click="loadEnvironment">
            Revérifier
          </button>
        </div>
      </div>
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
      <p v-if="!envOk" class="text-sm m-0" style="color:#8a1f1a">
        Génération bloquée tant que la configuration PHP / Composer n’est pas OK.
      </p>
      <div class="flex flex-wrap gap-3">
        <button type="button" class="forge-btn-primary" :disabled="!canGenerate" @click="runGenerate">
          {{ loading ? 'Génération…' : 'Générer' }}
        </button>
        <button type="button" class="forge-btn-ghost" :disabled="loading" @click="backToConfig">Modifier</button>
      </div>
    </section>

    <section v-show="step === 'result'" class="forge-card forge-report space-y-5">
      <div
        v-if="report"
        class="forge-report-banner"
        :class="reportOk ? 'is-ok' : 'is-err'"
      >
        <div class="forge-report-banner-icon" aria-hidden="true">
          <span v-if="reportOk">✓</span>
          <span v-else>!</span>
        </div>
        <div class="forge-report-banner-copy">
          <div class="forge-report-banner-top">
            <h2>{{ reportOk ? 'Projet généré' : 'Génération interrompue' }}</h2>
            <span class="forge-badge" :class="reportOk ? 'forge-badge-ok' : 'forge-badge-err'">
              {{ reportOk ? 'OK' : 'Échec' }}
            </span>
          </div>
          <p v-if="reportOk">Votre application est prête. Poursuivez depuis Mes projets (migrate, module…).</p>
          <p v-else>Corrigez l’erreur puis relancez une génération sur une destination vide.</p>
        </div>
      </div>

      <div v-if="report && report.destination" class="forge-report-path">
        <span class="forge-report-path-label">Destination</span>
        <code :title="report.destination">{{ destinationShort }}</code>
      </div>

      <ol v-if="reportSteps.length" class="forge-report-steps">
        <li
          v-for="(entry, i) in reportSteps"
          :key="i"
          class="forge-report-step"
          :class="'tone-' + entry.tone"
        >
          <span class="forge-report-step-mark" aria-hidden="true">
            <template v-if="entry.tone === 'ok'">✓</template>
            <template v-else-if="entry.tone === 'err'">×</template>
            <template v-else>{{ i + 1 }}</template>
          </span>
          <div class="forge-report-step-body">
            <strong>{{ entry.label }}</strong>
            <small v-if="entry.message">{{ entry.message }}</small>
          </div>
        </li>
      </ol>

      <div v-if="report && report.errors && report.errors.length" class="forge-report-errors" role="alert">
        <strong>Détails</strong>
        <ul>
          <li v-for="(err, i) in report.errors" :key="'e' + i">{{ err }}</li>
        </ul>
      </div>

      <div v-if="reportOk" class="forge-report-next">
        <a class="forge-next-card" href="/forge">
          <span class="forge-next-icon" aria-hidden="true">▦</span>
          <span>
            <strong>Mes projets</strong>
            <small>Ouvrir, migrer, créer un module</small>
          </span>
        </a>
        <button type="button" class="forge-next-card is-button" @click="backToConfig">
          <span class="forge-next-icon" aria-hidden="true">＋</span>
          <span>
            <strong>Autre projet</strong>
            <small>Relancer l’assistant</small>
          </span>
        </button>
      </div>
      <div v-else class="flex flex-wrap gap-3">
        <button type="button" class="forge-btn-primary" @click="backToConfig">Réessayer</button>
      </div>
    </section>
  </div>
</template>
