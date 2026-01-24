<template>
  <MainLayout>
    <div class="p-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold">Nouvelle Campagne SMS</h1>
        <p class="text-muted-foreground mt-2">Créez et planifiez votre campagne d'envoi</p>
      </div>

      <!-- Progress Steps -->
      <div class="mb-10">
        <div class="max-w-4xl mx-auto">
          <div class="relative flex items-center justify-between">
            <!-- Progress Line -->
            <div class="absolute top-5 left-0 right-0 h-1 bg-border -z-10"></div>
            <div
              class="absolute top-5 left-0 h-1 bg-primary transition-all duration-300 -z-10"
              :style="{ width: `${(currentStep / (steps.length - 1)) * 100}%` }"
            ></div>

            <!-- Steps -->
            <div
              v-for="(step, index) in steps"
              :key="index"
              class="flex flex-col items-center relative bg-background px-2"
            >
              <div
                class="w-12 h-12 rounded-full flex items-center justify-center font-bold mb-3 border-4 transition-all duration-300 shadow-md"
                :class="currentStep >= index
                  ? 'bg-primary text-primary-foreground border-primary scale-110'
                  : currentStep === index - 1
                    ? 'bg-background border-primary text-primary'
                    : 'bg-background border-border text-muted-foreground'"
              >
                <CheckCircleIcon v-if="currentStep > index" class="w-6 h-6" />
                <span v-else>{{ index + 1 }}</span>
              </div>
              <span
                class="text-sm font-medium text-center transition-colors"
                :class="currentStep >= index ? 'text-foreground' : 'text-muted-foreground'"
              >
                {{ step }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Container -->
      <div class="max-w-3xl mx-auto">
        <div class="rounded-lg border bg-card shadow-sm p-8">
          <!-- Step 1: Type d'envoi -->
          <div v-if="currentStep === 0" class="space-y-6">
            <h2 class="text-xl font-semibold mb-6">Type d'envoi</h2>

            <div class="space-y-4">
              <label class="flex items-start gap-4 p-4 border-2 rounded-lg cursor-pointer hover:bg-accent/50 transition-colors"
                :class="campaign.sendType === 'immediate' ? 'border-primary bg-primary/5' : 'border-border'">
                <input
                  v-model="campaign.sendType"
                  type="radio"
                  value="immediate"
                  class="mt-1 w-4 h-4"
                />
                <BoltIcon class="w-5 h-5 text-primary mt-0.5 flex-shrink-0" />
                <div class="flex-1">
                  <div class="font-semibold text-base flex items-center gap-2">Envoi immédiat</div>
                  <div class="text-sm text-muted-foreground mt-1">Envoyer les SMS maintenant à tous les destinataires</div>
                </div>
              </label>

              <label class="flex items-start gap-4 p-4 border-2 rounded-lg cursor-pointer hover:bg-accent/50 transition-colors"
                :class="campaign.sendType === 'scheduled' ? 'border-primary bg-primary/5' : 'border-border'">
                <input
                  v-model="campaign.sendType"
                  type="radio"
                  value="scheduled"
                  class="mt-1 w-4 h-4"
                />
                <CalendarIcon class="w-5 h-5 text-primary mt-0.5 flex-shrink-0" />
                <div class="flex-1">
                  <div class="font-semibold text-base flex items-center gap-2">Campagne planifiée</div>
                  <div class="text-sm text-muted-foreground mt-1">Planifier l'envoi à une date et heure précises</div>
                </div>
              </label>
            </div>

            <div class="space-y-4 mt-6">
              <div class="space-y-2">
                <label class="text-sm font-medium">Nom de la campagne</label>
                <input
                  v-model="campaign.name"
                  type="text"
                  placeholder="Ex: Promotion Été 2024"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
              </div>

              <div class="space-y-2">
                <label class="text-sm font-medium">Description (optionnelle)</label>
                <textarea
                  v-model="campaign.description"
                  placeholder="Description de la campagne..."
                  rows="2"
                  class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 resize-none"
                ></textarea>
              </div>
            </div>

            <div v-if="campaign.sendType === 'scheduled'" class="space-y-4 p-4 bg-muted/50 rounded-lg">
              <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="text-sm font-medium">Date d'envoi *</label>
                  <input
                    v-model="campaign.sendDate"
                    type="date"
                    :min="minDate"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                  />
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium">Heure d'envoi *</label>
                  <input
                    v-model="campaign.sendTime"
                    type="time"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                  />
                </div>
              </div>

              <!-- Recurrence Options -->
              <div class="space-y-3 pt-3 border-t border-border">
                <div class="flex items-center gap-2">
                  <input
                    v-model="campaign.isRecurring"
                    type="checkbox"
                    id="isRecurring"
                    class="w-4 h-4 rounded border-gray-300"
                  />
                  <label for="isRecurring" class="text-sm font-medium cursor-pointer">
                    Activer la récurrence
                  </label>
                </div>

                <div v-if="campaign.isRecurring" class="space-y-3 pl-6">
                  <div class="space-y-2">
                    <label class="text-sm font-medium">Fréquence</label>
                    <select
                      v-model="campaign.frequency"
                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                      <option value="daily">Quotidienne</option>
                      <option value="weekly">Hebdomadaire</option>
                      <option value="monthly">Mensuelle</option>
                    </select>
                  </div>

                  <div v-if="campaign.frequency === 'weekly'" class="space-y-2">
                    <label class="text-sm font-medium">Jours d'envoi</label>
                    <div class="flex flex-wrap gap-2">
                      <label
                        v-for="(day, index) in weekDays"
                        :key="index"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border cursor-pointer transition-colors"
                        :class="campaign.weekDays.includes(index + 1) ? 'bg-primary text-primary-foreground border-primary' : 'bg-background border-input hover:bg-accent'"
                      >
                        <input
                          type="checkbox"
                          :value="index + 1"
                          v-model="campaign.weekDays"
                          class="sr-only"
                        />
                        <span class="text-sm">{{ day }}</span>
                      </label>
                    </div>
                  </div>

                  <div v-if="campaign.frequency === 'monthly'" class="space-y-2">
                    <label class="text-sm font-medium">Jour du mois</label>
                    <select
                      v-model="campaign.dayOfMonth"
                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                      <option v-for="d in 28" :key="d" :value="d">{{ d }}</option>
                    </select>
                  </div>

                  <div class="space-y-2">
                    <label class="text-sm font-medium">Date de fin (optionnel)</label>
                    <input
                      v-model="campaign.endDate"
                      type="date"
                      :min="campaign.sendDate"
                      class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 2: Message -->
          <div v-if="currentStep === 1" class="space-y-6">
            <h2 class="text-xl font-semibold mb-6">Contenu du message</h2>

            <div class="space-y-2">
              <label class="text-sm font-medium">Utiliser un modèle (optionnel)</label>
              <select
                v-model="campaign.templateId"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              >
                <option value="">Écrire un nouveau message</option>
                <option v-for="template in templates" :key="template.id" :value="template.id.toString()">
                  {{ template.name }} ({{ template.message.length }} car.)
                </option>
              </select>
            </div>

            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <label class="text-sm font-medium">Message *</label>
                <div class="flex items-center gap-3 text-xs">
                  <span class="text-muted-foreground">{{ messageLength }}/160 caractères</span>
                  <span v-if="smsCount > 1" class="px-2 py-1 bg-warning/10 text-warning rounded font-medium">
                    {{ smsCount }} SMS
                  </span>
                </div>
              </div>
              <textarea
                v-model="campaign.message"
                placeholder="Votre message SMS..."
                rows="6"
                maxlength="320"
                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 resize-none font-mono"
              ></textarea>
              <p v-if="smsCount > 1" class="flex items-center gap-1.5 text-xs text-warning">
                <ExclamationTriangleIcon class="w-4 h-4" />
                <span>Votre message sera envoyé en {{ smsCount }} SMS (coût multiplié par {{ smsCount }})</span>
              </p>
            </div>

            <div class="p-4 bg-muted/50 rounded-lg">
              <p class="text-sm font-medium mb-3">Variables disponibles :</p>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="variable in variables"
                  :key="variable"
                  @click="insertVariable(variable)"
                  class="text-xs px-3 py-1.5 rounded-md bg-background hover:bg-primary hover:text-primary-foreground border border-border hover:border-primary transition-colors font-mono"
                >
                  {{ variable }}
                </button>
              </div>
            </div>

            <!-- A/B Testing Section -->
            <div class="space-y-3 pt-4 border-t">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <input
                    v-model="campaign.enableABTest"
                    type="checkbox"
                    id="enableABTest"
                    class="w-4 h-4 rounded border-gray-300"
                  />
                  <label for="enableABTest" class="text-sm font-medium cursor-pointer">
                    Activer le test A/B
                  </label>
                </div>
                <BeakerIcon class="w-5 h-5 text-muted-foreground" />
              </div>

              <div v-if="campaign.enableABTest" class="space-y-4 pl-6">
                <p class="text-sm text-muted-foreground">
                  Créez des variantes de votre message pour tester différentes versions.
                  Chaque variante sera envoyée à une portion égale de vos destinataires.
                </p>

                <div v-for="(variant, index) in campaign.variants" :key="index" class="space-y-2 p-3 bg-background rounded-lg border">
                  <div class="flex items-center justify-between">
                    <label class="text-sm font-medium">Variante {{ String.fromCharCode(65 + index) }}</label>
                    <button
                      v-if="campaign.variants.length > 2"
                      @click="removeVariant(index)"
                      class="text-destructive hover:bg-destructive/10 rounded p-1"
                    >
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>
                  <textarea
                    v-model="variant.message"
                    :placeholder="`Message variante ${String.fromCharCode(65 + index)}...`"
                    rows="3"
                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 resize-none font-mono"
                  ></textarea>
                  <div class="flex justify-between text-xs text-muted-foreground">
                    <span>{{ variant.message.length }}/160 caractères</span>
                    <span>{{ Math.ceil(100 / campaign.variants.length) }}% des destinataires</span>
                  </div>
                </div>

                <button
                  v-if="campaign.variants.length < 4"
                  @click="addVariant"
                  class="w-full inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-dashed border-input bg-background hover:bg-accent hover:text-accent-foreground h-10"
                >
                  <PlusIconOutline class="w-4 h-4" />
                  <span>Ajouter une variante ({{ campaign.variants.length }}/4)</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Step 3: Destinataires -->
          <div v-if="currentStep === 2" class="space-y-6">
            <h2 class="text-xl font-semibold mb-6">Sélection des destinataires</h2>

            <div class="space-y-3">
              <label class="flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-accent/50 transition-colors"
                :class="campaign.recipientType === 'all' ? 'border-primary bg-primary/5' : 'border-border'">
                <input
                  v-model="campaign.recipientType"
                  type="radio"
                  value="all"
                  class="mt-1 w-4 h-4"
                />
                <UsersIcon class="w-5 h-5 text-primary mt-0.5 flex-shrink-0" />
                <div class="flex-1">
                  <div class="font-semibold flex items-center gap-2">Tous les contacts</div>
                  <div class="text-sm text-muted-foreground mt-1">
                    Envoyer à tous les contacts actifs ({{ totalContacts }} contact{{ totalContacts > 1 ? 's' : '' }})
                  </div>
                </div>
              </label>

              <label class="flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-accent/50 transition-colors"
                :class="campaign.recipientType === 'group' ? 'border-primary bg-primary/5' : 'border-border'">
                <input
                  v-model="campaign.recipientType"
                  type="radio"
                  value="group"
                  class="mt-1 w-4 h-4"
                />
                <TagIcon class="w-5 h-5 text-primary mt-0.5 flex-shrink-0" />
                <div class="flex-1">
                  <div class="font-semibold flex items-center gap-2">Groupe spécifique</div>
                  <div class="text-sm text-muted-foreground mt-1">Sélectionner un groupe de contacts</div>
                </div>
              </label>
            </div>

            <div v-if="campaign.recipientType === 'group'" class="space-y-2 p-4 bg-muted/50 rounded-lg">
              <label class="text-sm font-medium">Sélectionner un groupe *</label>
              <select
                v-model="campaign.groupId"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              >
                <option value="">Choisir un groupe...</option>
                <option v-for="group in groups" :key="group.id" :value="group.id">
                  {{ group.name }} ({{ group.contact_count }} contacts)
                </option>
              </select>
            </div>

            <div class="p-5 bg-gradient-to-r from-primary/10 to-primary/5 border-2 border-primary/20 rounded-lg">
              <div class="flex items-start gap-4">
                <ChartPieIcon class="w-10 h-10 text-primary flex-shrink-0 mt-1" />
                <div class="flex-1">
                  <p class="font-bold text-lg mb-2">Estimation de l'envoi</p>
                  <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <p class="text-muted-foreground">Destinataires</p>
                      <p class="text-2xl font-bold text-foreground">{{ estimatedRecipients }}</p>
                    </div>
                    <div>
                      <p class="text-muted-foreground">SMS par message</p>
                      <p class="text-2xl font-bold" :class="smsCount > 1 ? 'text-warning' : 'text-foreground'">{{ smsCount }}</p>
                    </div>
                    <div>
                      <p class="text-muted-foreground">Total SMS</p>
                      <p class="text-2xl font-bold text-foreground">{{ estimatedRecipients * smsCount }}</p>
                    </div>
                    <div>
                      <p class="text-muted-foreground">Coût estimé</p>
                      <p class="text-2xl font-bold text-primary">{{ estimatedCost }} XAF</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 4: Récapitulatif -->
          <div v-if="currentStep === 3" class="space-y-6">
            <h2 class="text-xl font-semibold mb-6">Récapitulatif de la campagne</h2>

            <div class="space-y-4">
              <div class="p-4 bg-muted/30 rounded-lg space-y-3">
                <div>
                  <p class="text-xs text-muted-foreground uppercase">Type d'envoi</p>
                  <p class="text-base font-semibold flex items-center gap-2">
                    <BoltIcon v-if="campaign.sendType === 'immediate'" class="w-5 h-5 text-primary" />
                    <ArrowPathIcon v-else-if="campaign.isRecurring" class="w-5 h-5 text-primary" />
                    <CalendarIcon v-else class="w-5 h-5 text-primary" />
                    <span>{{ campaign.sendType === 'immediate' ? 'Envoi immédiat' : (campaign.isRecurring ? 'Campagne récurrente' : 'Campagne planifiée') }}</span>
                  </p>
                  <p v-if="campaign.sendType === 'scheduled'" class="text-sm text-muted-foreground">
                    {{ campaign.isRecurring ? 'Début:' : 'Programmé pour le' }} {{ campaign.sendDate }} à {{ campaign.sendTime }}
                  </p>
                  <p v-if="campaign.isRecurring" class="text-sm text-muted-foreground">
                    Fréquence: {{ { daily: 'Quotidienne', weekly: 'Hebdomadaire', monthly: 'Mensuelle' }[campaign.frequency] }}
                    <span v-if="campaign.frequency === 'weekly'"> ({{ campaign.weekDays.map(d => weekDays[d-1]).join(', ') }})</span>
                    <span v-if="campaign.frequency === 'monthly'"> (le {{ campaign.dayOfMonth }} de chaque mois)</span>
                  </p>
                  <p v-if="campaign.isRecurring && campaign.endDate" class="text-sm text-muted-foreground">
                    Fin: {{ campaign.endDate }}
                  </p>
                </div>
              </div>

              <div class="p-4 bg-muted/30 rounded-lg space-y-2">
                <p class="text-xs text-muted-foreground uppercase">Nom de la campagne</p>
                <p class="text-base font-semibold">{{ campaign.name || 'Sans nom' }}</p>
                <p v-if="campaign.description" class="text-sm text-muted-foreground">{{ campaign.description }}</p>
              </div>

              <div class="p-4 bg-muted/30 rounded-lg space-y-2">
                <p class="text-xs text-muted-foreground uppercase">Message</p>
                <div class="p-3 bg-background rounded border font-mono text-sm whitespace-pre-wrap">{{ campaign.message || 'Aucun message' }}</div>
                <p class="text-xs text-muted-foreground">{{ messageLength }} caractères • {{ smsCount }} SMS</p>
              </div>

              <!-- A/B Testing Summary -->
              <div v-if="campaign.enableABTest" class="p-4 bg-muted/30 rounded-lg space-y-3">
                <p class="text-xs text-muted-foreground uppercase flex items-center gap-2">
                  <BeakerIcon class="w-4 h-4" />
                  Test A/B activé
                </p>
                <div class="space-y-2">
                  <div
                    v-for="(variant, index) in campaign.variants"
                    :key="index"
                    class="p-3 bg-background rounded border"
                  >
                    <p class="text-xs font-medium text-primary mb-1">
                      Variante {{ String.fromCharCode(65 + index) }} ({{ Math.ceil(100 / campaign.variants.length) }}%)
                    </p>
                    <p class="font-mono text-sm whitespace-pre-wrap">{{ variant.message || 'Aucun message' }}</p>
                  </div>
                </div>
              </div>

              <div class="p-4 bg-muted/30 rounded-lg space-y-2">
                <p class="text-xs text-muted-foreground uppercase">Destinataires</p>
                <p class="text-base font-semibold flex items-center gap-2">
                  <UsersIcon v-if="campaign.recipientType === 'all'" class="w-5 h-5 text-primary" />
                  <TagIcon v-else class="w-5 h-5 text-primary" />
                  <span>{{ campaign.recipientType === 'all' ? 'Tous les contacts' : 'Groupe spécifique' }}</span>
                </p>
                <p v-if="campaign.recipientType === 'group'" class="text-sm text-muted-foreground">
                  {{ groups.find(g => g.id === campaign.groupId)?.name || 'Groupe non sélectionné' }}
                </p>
                <p class="text-sm text-primary font-medium">{{ estimatedRecipients }} contact(s)</p>
              </div>

              <div class="p-5 bg-gradient-to-r from-success/20 to-success/10 border-2 border-success/30 rounded-lg">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm text-muted-foreground mb-1">Coût total estimé</p>
                    <p class="text-3xl font-bold text-success">{{ estimatedCost }} XAF</p>
                    <p class="text-xs text-muted-foreground mt-1">{{ estimatedRecipients * smsCount }} SMS au total</p>
                  </div>
                  <CheckCircleIcon class="w-16 h-16 text-success/50" />
                </div>
              </div>
            </div>
          </div>

          <!-- Navigation Buttons -->
          <div class="flex justify-between mt-8 pt-6 border-t">
            <button
              v-if="currentStep > 0"
              @click="currentStep--"
              :disabled="submitting"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
            >
              ← Précédent
            </button>
            <div v-else></div>

            <div class="flex gap-3">
              <button
                v-if="currentStep < steps.length - 1"
                @click="saveDraft"
                :disabled="submitting"
                class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
              >
                <DocumentArrowDownIcon class="w-4 h-4" />
                <span>{{ submitting ? 'Sauvegarde...' : 'Brouillon' }}</span>
              </button>
              <button
                v-if="currentStep < steps.length - 1"
                @click="currentStep++"
                :disabled="!canGoNext() || submitting"
                class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
              >
                Suivant →
              </button>
              <button
                v-else
                @click="launchCampaign"
                :disabled="submitting"
                class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-10 px-6 py-2"
                :class="campaign.sendType === 'immediate' ? 'bg-success text-success-foreground hover:bg-success/90' : 'bg-primary text-primary-foreground hover:bg-primary/90'"
              >
                <RocketLaunchIcon class="w-5 h-5" />
                <span v-if="submitting">Envoi en cours...</span>
                <span v-else>{{ campaign.sendType === 'immediate' ? 'Envoyer maintenant' : 'Planifier la campagne' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import MainLayout from '@/components/MainLayout.vue'
import {
  ChartPieIcon,
  DocumentArrowDownIcon,
  RocketLaunchIcon,
  CheckCircleIcon,
  BoltIcon,
  CalendarIcon,
  ExclamationTriangleIcon,
  UsersIcon,
  TagIcon,
  ArrowPathIcon,
  BeakerIcon,
  TrashIcon,
  PlusIcon as PlusIconOutline
} from '@heroicons/vue/24/outline'
import { campaignService } from '@/services/campaignService'
import { templateService, type Template } from '@/services/templateService'
import { contactService } from '@/services/contactService'
import { showSuccess, showError, showConfirm } from '@/utils/notifications'

const router = useRouter()
const route = useRoute()

const steps = ['Type d\'envoi', 'Message', 'Destinataires', 'Récapitulatif']
const currentStep = ref(0)
const loading = ref(false)
const submitting = ref(false)

const variables = ['{{name}}', '{{email}}', '{{phone}}', '{{code}}']
const templates = ref<Template[]>([])
const contacts = ref<any[]>([])
const groups = ref<any[]>([])
const weekDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const campaign = ref({
  name: '',
  description: '',
  sendType: 'immediate', // immediate ou scheduled
  sendDate: '',
  sendTime: '09:00',
  templateId: '',
  message: '',
  recipientType: 'all',
  groupId: '',
  // Recurrence options
  isRecurring: false,
  frequency: 'weekly' as 'daily' | 'weekly' | 'monthly',
  weekDays: [1, 2, 3, 4, 5] as number[], // Lun-Ven par défaut
  dayOfMonth: 1,
  endDate: '',
  // A/B Testing options
  enableABTest: false,
  variants: [{ message: '' }, { message: '' }] as { message: string }[]
})

const messageLength = computed(() => campaign.value.message.length)
const smsCount = computed(() => Math.ceil(campaign.value.message.length / 160))

const totalContacts = computed(() => contacts.value.filter(c => c.status === 'active').length)

const estimatedRecipients = computed(() => {
  if (campaign.value.recipientType === 'all') return totalContacts.value
  if (campaign.value.recipientType === 'group') {
    const group = groups.value.find(g => g.id === campaign.value.groupId)
    return group?.contact_count || 0
  }
  return 0
})

const estimatedCost = computed(() => {
  const costPerSMS = 20 // 20 FCFA par SMS (Airtel/Moov Gabon)
  return Math.round(estimatedRecipients.value * smsCount.value * costPerSMS)
})

async function loadData() {
  loading.value = true
  try {
    const [templatesData, contactsData] = await Promise.all([
      templateService.getAll(),
      contactService.getAll()
    ])
    templates.value = templatesData.filter(t => t.status === 'active')
    contacts.value = contactsData

    // Simuler des groupes (à remplacer par API réelle)
    groups.value = [
      { id: '1', name: 'Clients VIP', contact_count: 234 },
      { id: '2', name: 'Nouveaux clients', contact_count: 156 },
      { id: '3', name: 'Région Paris', contact_count: 445 }
    ]

    // Si templateId dans query params, charger le template
    if (route.query.templateId) {
      const templateId = route.query.templateId.toString()
      campaign.value.templateId = templateId
      applyTemplate(templateId)
    }
  } catch (err) {
    console.error('Error loading data:', err)
  } finally {
    loading.value = false
  }
}

function applyTemplate(templateId: string) {
  const template = templates.value.find(t => t.id.toString() === templateId)
  if (template) {
    campaign.value.message = template.message
  }
}

watch(() => campaign.value.templateId, (newValue) => {
  if (newValue) {
    applyTemplate(newValue)
  }
})

function insertVariable(variable: string) {
  campaign.value.message += variable
}

function addVariant() {
  if (campaign.value.variants.length < 4) {
    campaign.value.variants.push({ message: '' })
  }
}

function removeVariant(index: number) {
  if (campaign.value.variants.length > 2) {
    campaign.value.variants.splice(index, 1)
  }
}

async function saveDraft() {
  submitting.value = true
  try {
    const data = {
      name: campaign.value.name || 'Brouillon',
      description: campaign.value.description,
      message: campaign.value.message,
      status: 'draft',
      recipient_type: campaign.value.recipientType,
      group_id: campaign.value.groupId || null,
      scheduled_at: campaign.value.sendType === 'scheduled' && campaign.value.sendDate
        ? `${campaign.value.sendDate} ${campaign.value.sendTime || '09:00'}`
        : null
    }
    await campaignService.create(data)
    showSuccess('Brouillon sauvegardé avec succès !')
  } catch (err: any) {
    showError(err.message || 'Erreur lors de la sauvegarde')
  } finally {
    submitting.value = false
  }
}

async function launchCampaign() {
  if (!campaign.value.name) {
    showError('Veuillez donner un nom à la campagne')
    return
  }
  if (!campaign.value.message) {
    showError('Veuillez saisir un message')
    return
  }

  let confirmMessage = ''
  if (campaign.value.sendType === 'immediate') {
    confirmMessage = `Envoyer immédiatement ${estimatedRecipients.value} SMS ?`
  } else if (campaign.value.isRecurring) {
    const freqLabel = { daily: 'quotidienne', weekly: 'hebdomadaire', monthly: 'mensuelle' }[campaign.value.frequency]
    confirmMessage = `Planifier une campagne ${freqLabel} à partir du ${campaign.value.sendDate} à ${campaign.value.sendTime} ?`
  } else {
    confirmMessage = `Planifier l'envoi de ${estimatedRecipients.value} SMS pour le ${campaign.value.sendDate} à ${campaign.value.sendTime} ?`
  }

  const confirmTitle = campaign.value.sendType === 'immediate' ? 'Envoyer maintenant' : 'Planifier la campagne'
  const confirmed = await showConfirm(confirmTitle, confirmMessage)
  if (!confirmed) return

  submitting.value = true
  try {
    const data: any = {
      name: campaign.value.name,
      description: campaign.value.description,
      message: campaign.value.message,
      status: campaign.value.sendType === 'immediate' ? 'sending' : 'scheduled',
      recipient_type: campaign.value.recipientType,
      group_id: campaign.value.groupId || null,
      scheduled_at: campaign.value.sendType === 'scheduled' && campaign.value.sendDate
        ? `${campaign.value.sendDate} ${campaign.value.sendTime || '09:00'}`
        : null,
      messages_sent: campaign.value.sendType === 'immediate' ? estimatedRecipients.value : 0,
      // A/B Testing
      ab_testing_enabled: campaign.value.enableABTest,
      variants: campaign.value.enableABTest
        ? campaign.value.variants.filter(v => v.message.trim()).map((v, i) => ({
            name: `Variante ${String.fromCharCode(65 + i)}`,
            message: v.message
          }))
        : null
    }

    const createdCampaign = await campaignService.create(data)

    // If recurring, create schedule
    if (campaign.value.sendType === 'scheduled' && campaign.value.isRecurring && createdCampaign.id) {
      const scheduleData = {
        frequency: campaign.value.frequency,
        time: campaign.value.sendTime,
        days_of_week: campaign.value.frequency === 'weekly' ? campaign.value.weekDays : null,
        day_of_month: campaign.value.frequency === 'monthly' ? campaign.value.dayOfMonth : null,
        start_date: campaign.value.sendDate,
        end_date: campaign.value.endDate || null
      }
      await campaignService.createSchedule(createdCampaign.id, scheduleData)
    }

    if (campaign.value.sendType === 'immediate') {
      showSuccess(`${estimatedRecipients.value} SMS envoyés avec succès !`)
    } else if (campaign.value.isRecurring) {
      showSuccess('Campagne récurrente planifiée avec succès !')
    } else {
      showSuccess('Campagne planifiée avec succès !')
    }

    router.push('/dashboard')
  } catch (err: any) {
    showError(err.message || 'Erreur lors du lancement')
  } finally {
    submitting.value = false
  }
}

function canGoNext(): boolean {
  if (currentStep.value === 0) {
    return campaign.value.sendType === 'immediate' || (!!campaign.value.sendDate && !!campaign.value.sendTime)
  }
  if (currentStep.value === 1) {
    // Check main message or A/B variants
    if (campaign.value.enableABTest) {
      // All variants must have a message
      return campaign.value.variants.every(v => v.message.trim().length > 0)
    }
    return campaign.value.message.length > 0
  }
  if (currentStep.value === 2) {
    return campaign.value.recipientType === 'all' || (campaign.value.recipientType === 'group' && !!campaign.value.groupId)
  }
  return true
}

onMounted(() => {
  loadData()
})
</script>
