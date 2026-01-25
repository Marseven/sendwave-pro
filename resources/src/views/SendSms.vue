<template>
  <MainLayout>
    <div class="p-4 sm:p-6 lg:p-8">
      <!-- Header -->
      <div class="mb-4 sm:mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
          <div>
            <h1 class="text-xl sm:text-2xl font-bold flex items-center gap-2">
              <PaperAirplaneIcon class="w-6 h-6 sm:w-7 sm:h-7 text-primary" />
              Envoi de SMS
            </h1>
            <p class="text-sm text-muted-foreground mt-1">Envoyez des SMS unitaires ou en masse</p>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="showTemplates = true"
              class="inline-flex items-center gap-1 sm:gap-2 px-3 sm:px-4 py-2 border rounded-lg hover:bg-accent transition-colors text-xs sm:text-sm font-medium"
            >
              <DocumentTextIcon class="w-4 h-4" />
              <span class="hidden sm:inline">Modèles</span>
            </button>
            <button
              @click="saveDraft"
              :disabled="!message"
              class="inline-flex items-center gap-1 sm:gap-2 px-3 sm:px-4 py-2 border rounded-lg hover:bg-accent transition-colors text-xs sm:text-sm font-medium disabled:opacity-50"
            >
              <BookmarkIcon class="w-4 h-4" />
              <span class="hidden sm:inline">Brouillon</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Tabs with horizontal scroll on mobile -->
      <div class="overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0">
        <TabNav
          v-model="activeTab"
          :tabs="tabs"
          class="mb-4 sm:mb-6"
        />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-4 sm:space-y-6">
          <!-- Send SMS Tab -->
          <div v-if="activeTab === 'send'" class="space-y-4 sm:space-y-6">
            <div class="rounded-lg border bg-card p-4 sm:p-6 space-y-4 sm:space-y-6">
              <!-- Channel & Route Selection -->
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                <div class="space-y-1.5 sm:space-y-2">
                  <label class="text-xs sm:text-sm font-medium">Canal de message</label>
                  <select
                    v-model="channel"
                    class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-primary"
                  >
                    <option value="transactional">Transactionnel</option>
                    <option value="marketing">Marketing</option>
                  </select>
                </div>
                <div class="space-y-1.5 sm:space-y-2">
                  <label class="text-xs sm:text-sm font-medium">Route du message</label>
                  <select
                    v-model="route"
                    class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-primary"
                  >
                    <option value="auto">Auto (Recommandé)</option>
                    <option value="airtel">Airtel Direct</option>
                    <option value="moov">Moov Direct</option>
                  </select>
                </div>
                <div class="space-y-1.5 sm:space-y-2 sm:col-span-2 lg:col-span-1">
                  <label class="text-xs sm:text-sm font-medium">ID Expéditeur</label>
                  <select
                    v-model="senderId"
                    class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-primary"
                  >
                    <option value="JOBSSMS">JOBSSMS (Défaut)</option>
                    <option value="SendWave">SendWave</option>
                  </select>
                </div>
              </div>

              <!-- Recipients -->
              <div class="space-y-1.5 sm:space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-2">
                  <label class="text-xs sm:text-sm font-medium">Destinataires</label>
                  <div class="flex items-center gap-2">
                    <button
                      @click="showContactPicker = true"
                      class="text-xs text-primary hover:underline flex items-center gap-1"
                    >
                      <UserGroupIcon class="w-4 h-4" />
                      Contacts
                    </button>
                    <button
                      @click="showGroupPicker = true"
                      class="text-xs text-primary hover:underline flex items-center gap-1"
                    >
                      <FolderIcon class="w-4 h-4" />
                      Groupes
                    </button>
                  </div>
                </div>
                <div class="relative">
                  <textarea
                    v-model="recipients"
                    placeholder="Entrez les numéros (un par ligne ou séparés par virgule)&#10;+241 77 75 07 37&#10;62 34 56 78"
                    rows="3"
                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-xs sm:text-sm font-mono focus:ring-2 focus:ring-primary resize-none"
                  ></textarea>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 text-xs text-muted-foreground">
                  <span>{{ recipientCount }} destinataire(s) - Airtel: {{ operatorStats.airtel }}, Moov: {{ operatorStats.moov }}</span>
                  <span v-if="operatorStats.invalid > 0" class="text-destructive">{{ operatorStats.invalid }} invalide(s)</span>
                </div>
              </div>

              <!-- Message -->
              <div class="space-y-1.5 sm:space-y-2">
                <div class="flex items-center justify-between">
                  <label class="text-xs sm:text-sm font-medium">Message</label>
                  <div class="flex items-center gap-2 sm:gap-3 text-xs">
                    <span class="text-muted-foreground">{{ messageLength }}/160</span>
                    <span v-if="smsCount > 1" class="px-2 py-0.5 bg-warning/10 text-warning rounded font-medium">
                      {{ smsCount }} SMS
                    </span>
                  </div>
                </div>
                <textarea
                  v-model="message"
                  placeholder="Tapez votre message ici..."
                  rows="5"
                  maxlength="640"
                  class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-xs sm:text-sm focus:ring-2 focus:ring-primary resize-none"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Send Opt SMS Tab (Advanced) -->
          <div v-if="activeTab === 'opt'" class="space-y-6">
            <div class="rounded-lg border bg-card p-6 space-y-6">
              <div class="flex items-center gap-2 pb-4 border-b">
                <CogIcon class="w-5 h-5 text-primary" />
                <h3 class="font-semibold">Options avancées</h3>
              </div>

              <!-- Same fields as Send SMS -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                  <label class="text-sm font-medium">Canal de message</label>
                  <select v-model="channel" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    <option value="transactional">Transactionnel</option>
                    <option value="marketing">Marketing</option>
                  </select>
                </div>
                <div class="space-y-2">
                  <label class="text-sm font-medium">Route du message</label>
                  <select v-model="route" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                    <option value="auto">Auto</option>
                    <option value="airtel">Airtel Direct</option>
                    <option value="moov">Moov Direct</option>
                  </select>
                </div>
              </div>

              <!-- Recipients -->
              <div class="space-y-2">
                <label class="text-sm font-medium">Destinataires</label>
                <textarea
                  v-model="recipients"
                  placeholder="Numéros de téléphone..."
                  rows="3"
                  class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm font-mono resize-none"
                ></textarea>
              </div>

              <!-- Message -->
              <div class="space-y-2">
                <label class="text-sm font-medium">Message</label>
                <textarea
                  v-model="message"
                  placeholder="Votre message..."
                  rows="4"
                  class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm resize-none"
                ></textarea>
              </div>

              <!-- Advanced Options -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t">
                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-accent/50">
                  <input type="checkbox" v-model="options.unicode" class="w-4 h-4 rounded" />
                  <div>
                    <p class="font-medium text-sm">Support Unicode</p>
                    <p class="text-xs text-muted-foreground">Caractères spéciaux et emojis</p>
                  </div>
                </label>
                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-accent/50">
                  <input type="checkbox" v-model="options.flash" class="w-4 h-4 rounded" />
                  <div>
                    <p class="font-medium text-sm">SMS Flash</p>
                    <p class="text-xs text-muted-foreground">Affichage immédiat</p>
                  </div>
                </label>
                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-accent/50">
                  <input type="checkbox" v-model="options.dlr" class="w-4 h-4 rounded" />
                  <div>
                    <p class="font-medium text-sm">Rapport de livraison</p>
                    <p class="text-xs text-muted-foreground">Accusé de réception</p>
                  </div>
                </label>
                <label class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-accent/50">
                  <input type="checkbox" v-model="options.priority" class="w-4 h-4 rounded" />
                  <div>
                    <p class="font-medium text-sm">Prioritaire</p>
                    <p class="text-xs text-muted-foreground">Envoi en priorité haute</p>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <!-- SMS From File Tab -->
          <div v-if="activeTab === 'file'" class="space-y-6">
            <div class="rounded-lg border bg-card p-6 space-y-6">
              <div class="flex items-center gap-2 pb-4 border-b">
                <DocumentArrowUpIcon class="w-5 h-5 text-primary" />
                <h3 class="font-semibold">Import depuis fichier</h3>
              </div>

              <!-- File Upload -->
              <div
                @dragover.prevent="isDragging = true"
                @dragleave="isDragging = false"
                @drop.prevent="handleFileDrop"
                class="border-2 border-dashed rounded-lg p-8 text-center transition-colors"
                :class="isDragging ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/50'"
              >
                <input
                  type="file"
                  ref="fileInput"
                  @change="handleFileSelect"
                  accept=".csv,.xlsx,.xls,.txt"
                  class="hidden"
                />
                <CloudArrowUpIcon class="w-12 h-12 mx-auto text-muted-foreground mb-4" />
                <p class="text-sm font-medium mb-2">Glissez-déposez votre fichier ici</p>
                <p class="text-xs text-muted-foreground mb-4">ou</p>
                <button
                  @click="$refs.fileInput.click()"
                  class="px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90"
                >
                  Parcourir
                </button>
                <p class="text-xs text-muted-foreground mt-4">
                  Formats acceptés: CSV, Excel (.xlsx, .xls), TXT
                </p>
              </div>

              <!-- Uploaded File Info -->
              <div v-if="uploadedFile" class="p-4 bg-muted/50 rounded-lg">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <DocumentTextIcon class="w-8 h-8 text-primary" />
                    <div>
                      <p class="font-medium">{{ uploadedFile.name }}</p>
                      <p class="text-xs text-muted-foreground">{{ uploadedFile.rows }} lignes détectées</p>
                    </div>
                  </div>
                  <button @click="uploadedFile = null" class="text-muted-foreground hover:text-destructive">
                    <XMarkIcon class="w-5 h-5" />
                  </button>
                </div>
              </div>

              <!-- Column Mapping -->
              <div v-if="uploadedFile" class="space-y-4">
                <h4 class="font-medium">Mapping des colonnes</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="space-y-2">
                    <label class="text-sm font-medium">Colonne Telephone</label>
                    <select v-model="columnMapping.phone" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                      <option v-for="col in uploadedFile.columns" :key="col" :value="col">{{ col }}</option>
                    </select>
                  </div>
                  <div class="space-y-2">
                    <label class="text-sm font-medium">Colonne Nom (optionnel)</label>
                    <select v-model="columnMapping.name" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                      <option value="">-- Aucune --</option>
                      <option v-for="col in uploadedFile.columns" :key="col" :value="col">{{ col }}</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Message for file import -->
              <div v-if="uploadedFile" class="space-y-2">
                <label class="text-sm font-medium">Message (variables: {name}, {phone})</label>
                <textarea
                  v-model="message"
                  placeholder="Bonjour {name}, votre code est..."
                  rows="4"
                  class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm resize-none"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
            <button
              @click="sendNow"
              :disabled="!canSend || sending"
              class="flex-1 inline-flex items-center justify-center gap-2 h-10 sm:h-12 px-4 sm:px-6 bg-success text-white rounded-lg text-sm sm:text-base font-medium hover:bg-success/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <PaperAirplaneIcon v-if="!sending" class="w-4 h-4 sm:w-5 sm:h-5" />
              <div v-else class="animate-spin rounded-full h-4 w-4 sm:h-5 sm:w-5 border-b-2 border-white"></div>
              {{ sending ? 'Envoi...' : 'Envoyer' }}
            </button>
            <button
              @click="showScheduleModal = true"
              :disabled="!canSend"
              class="inline-flex items-center justify-center gap-2 h-10 sm:h-12 px-4 sm:px-6 border rounded-lg text-sm sm:text-base font-medium hover:bg-accent disabled:opacity-50 transition-colors"
            >
              <ClockIcon class="w-4 h-4 sm:w-5 sm:h-5" />
              Planifier
            </button>
          </div>

          <!-- Mobile Cost Summary (shown only on mobile) -->
          <div class="lg:hidden rounded-lg border bg-card p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <CalculatorIcon class="w-5 h-5 text-primary" />
                <span class="font-semibold text-sm">Estimation</span>
              </div>
              <span class="text-lg font-bold text-primary">{{ estimatedCost }} XAF</span>
            </div>
            <div class="flex gap-4 mt-2 text-xs text-muted-foreground">
              <span>{{ recipientCount }} dest.</span>
              <span>{{ smsCount }} SMS/msg</span>
              <span>{{ totalSms }} total</span>
            </div>
          </div>
        </div>

        <!-- Sidebar (hidden on mobile) -->
        <div class="hidden lg:block space-y-4 sm:space-y-6">
          <!-- Cost Summary -->
          <div class="rounded-lg border bg-card p-4 sm:p-6">
            <h3 class="font-semibold mb-3 sm:mb-4 flex items-center gap-2 text-sm sm:text-base">
              <CalculatorIcon class="w-5 h-5 text-primary" />
              Estimation
            </h3>
            <div class="space-y-2 sm:space-y-3">
              <div class="flex justify-between text-xs sm:text-sm">
                <span class="text-muted-foreground">Destinataires</span>
                <span class="font-medium">{{ recipientCount }}</span>
              </div>
              <div class="flex justify-between text-xs sm:text-sm">
                <span class="text-muted-foreground">SMS par message</span>
                <span class="font-medium" :class="smsCount > 1 ? 'text-warning' : ''">{{ smsCount }}</span>
              </div>
              <div class="flex justify-between text-xs sm:text-sm">
                <span class="text-muted-foreground">Total SMS</span>
                <span class="font-medium">{{ totalSms }}</span>
              </div>
              <div class="border-t pt-2 sm:pt-3 mt-2 sm:mt-3">
                <div class="flex justify-between">
                  <span class="font-medium text-sm">Coût estimé</span>
                  <span class="text-lg sm:text-xl font-bold text-primary">{{ estimatedCost }} XAF</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="rounded-lg border bg-card p-4 sm:p-6">
            <h3 class="font-semibold mb-3 sm:mb-4 text-sm sm:text-base">Actions rapides</h3>
            <div class="space-y-2">
              <button
                @click="showContactPicker = true"
                class="w-full flex items-center gap-2 sm:gap-3 p-2 sm:p-3 rounded-lg border hover:bg-accent transition-colors text-left"
              >
                <UserGroupIcon class="w-4 h-4 sm:w-5 sm:h-5 text-primary flex-shrink-0" />
                <div class="min-w-0">
                  <p class="font-medium text-xs sm:text-sm">Ajouter des contacts</p>
                  <p class="text-xs text-muted-foreground hidden sm:block">Depuis votre répertoire</p>
                </div>
              </button>
              <button
                @click="showGroupPicker = true"
                class="w-full flex items-center gap-2 sm:gap-3 p-2 sm:p-3 rounded-lg border hover:bg-accent transition-colors text-left"
              >
                <FolderIcon class="w-4 h-4 sm:w-5 sm:h-5 text-primary flex-shrink-0" />
                <div class="min-w-0">
                  <p class="font-medium text-xs sm:text-sm">Sélectionner un groupe</p>
                  <p class="text-xs text-muted-foreground hidden sm:block">Envoi à un groupe entier</p>
                </div>
              </button>
              <button
                @click="showTemplates = true"
                class="w-full flex items-center gap-2 sm:gap-3 p-2 sm:p-3 rounded-lg border hover:bg-accent transition-colors text-left"
              >
                <DocumentTextIcon class="w-4 h-4 sm:w-5 sm:h-5 text-primary flex-shrink-0" />
                <div class="min-w-0">
                  <p class="font-medium text-xs sm:text-sm">Utiliser un modèle</p>
                  <p class="text-xs text-muted-foreground hidden sm:block">Messages prédéfinis</p>
                </div>
              </button>
            </div>
          </div>

          <!-- Recent Templates -->
          <div class="rounded-lg border bg-card p-4 sm:p-6">
            <h3 class="font-semibold mb-3 sm:mb-4 text-sm sm:text-base">Modèles récents</h3>
            <div class="space-y-2">
              <button
                v-for="template in recentTemplates"
                :key="template.id"
                @click="useTemplate(template)"
                class="w-full p-2 sm:p-3 rounded-lg border hover:bg-accent transition-colors text-left"
              >
                <p class="font-medium text-xs sm:text-sm truncate">{{ template.name }}</p>
                <p class="text-xs text-muted-foreground truncate">{{ template.content }}</p>
              </button>
              <p v-if="recentTemplates.length === 0" class="text-xs sm:text-sm text-muted-foreground text-center py-3 sm:py-4">
                Aucun modèle récent
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Schedule Modal -->
    <div v-if="showScheduleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="showScheduleModal = false"></div>
      <div class="relative bg-card rounded-lg shadow-lg w-full max-w-sm sm:max-w-md p-4 sm:p-6">
        <h3 class="text-base sm:text-lg font-semibold mb-3 sm:mb-4">Planifier l'envoi</h3>
        <div class="space-y-3 sm:space-y-4">
          <div class="space-y-1.5 sm:space-y-2">
            <label class="text-xs sm:text-sm font-medium">Date</label>
            <input
              type="date"
              v-model="scheduleDate"
              :min="today"
              class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-xs sm:text-sm"
            />
          </div>
          <div class="space-y-1.5 sm:space-y-2">
            <label class="text-xs sm:text-sm font-medium">Heure</label>
            <input
              type="time"
              v-model="scheduleTime"
              class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-xs sm:text-sm"
            />
          </div>
        </div>
        <div class="flex gap-2 sm:gap-3 mt-4 sm:mt-6">
          <button
            @click="scheduleMessage"
            :disabled="!scheduleDate || !scheduleTime"
            class="flex-1 h-9 sm:h-10 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90 disabled:opacity-50"
          >
            Planifier
          </button>
          <button
            @click="showScheduleModal = false"
            class="px-3 sm:px-4 h-9 sm:h-10 border rounded-lg text-sm hover:bg-accent"
          >
            Annuler
          </button>
        </div>
      </div>
    </div>

    <!-- Contact Picker Modal -->
    <div v-if="showContactPicker" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="showContactPicker = false"></div>
      <div class="relative bg-card rounded-lg shadow-lg w-full max-w-sm sm:max-w-lg p-4 sm:p-6 max-h-[85vh] overflow-hidden flex flex-col">
        <h3 class="text-base sm:text-lg font-semibold mb-3 sm:mb-4">Sélectionner des contacts</h3>
        <input
          type="text"
          v-model="contactSearch"
          placeholder="Rechercher..."
          class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-xs sm:text-sm mb-3 sm:mb-4"
        />
        <div class="flex-1 overflow-y-auto space-y-2 min-h-0">
          <label
            v-for="contact in filteredContacts"
            :key="contact.id"
            class="flex items-center gap-2 sm:gap-3 p-2 sm:p-3 rounded-lg border hover:bg-accent cursor-pointer"
          >
            <input
              type="checkbox"
              :value="contact.phone"
              v-model="selectedContacts"
              class="w-4 h-4 rounded flex-shrink-0"
            />
            <div class="min-w-0">
              <p class="font-medium text-xs sm:text-sm truncate">{{ contact.name }}</p>
              <p class="text-xs text-muted-foreground truncate">{{ contact.phone }}</p>
            </div>
          </label>
        </div>
        <div class="flex gap-2 sm:gap-3 mt-3 sm:mt-4 pt-3 sm:pt-4 border-t">
          <button
            @click="addSelectedContacts"
            class="flex-1 h-9 sm:h-10 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90"
          >
            Ajouter ({{ selectedContacts.length }})
          </button>
          <button
            @click="showContactPicker = false"
            class="px-3 sm:px-4 h-9 sm:h-10 border rounded-lg text-sm hover:bg-accent"
          >
            Annuler
          </button>
        </div>
      </div>
    </div>

    <!-- Templates Modal -->
    <div v-if="showTemplates" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="showTemplates = false"></div>
      <div class="relative bg-card rounded-lg shadow-lg w-full max-w-sm sm:max-w-lg p-4 sm:p-6 max-h-[85vh] overflow-hidden flex flex-col">
        <h3 class="text-base sm:text-lg font-semibold mb-3 sm:mb-4">Modèles</h3>
        <div class="flex-1 overflow-y-auto space-y-2 min-h-0">
          <button
            v-for="template in templates"
            :key="template.id"
            @click="useTemplate(template); showTemplates = false"
            class="w-full p-3 sm:p-4 rounded-lg border hover:bg-accent transition-colors text-left"
          >
            <p class="font-medium text-sm">{{ template.name }}</p>
            <p class="text-xs sm:text-sm text-muted-foreground mt-1 line-clamp-2">{{ template.content }}</p>
          </button>
        </div>
        <button
          @click="showTemplates = false"
          class="mt-3 sm:mt-4 w-full h-9 sm:h-10 border rounded-lg text-sm hover:bg-accent"
        >
          Fermer
        </button>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import MainLayout from '@/components/MainLayout.vue'
import TabNav from '@/components/ui/TabNav.vue'
import {
  PaperAirplaneIcon,
  DocumentTextIcon,
  BookmarkIcon,
  UserGroupIcon,
  FolderIcon,
  CogIcon,
  DocumentArrowUpIcon,
  CloudArrowUpIcon,
  XMarkIcon,
  ClockIcon,
  CalculatorIcon
} from '@heroicons/vue/24/outline'
import { contactService } from '@/services/contactService'
import apiClient from '@/services/api'
import { showSuccess, showError, showConfirm } from '@/utils/notifications'

interface Contact {
  id: number
  name: string
  phone: string
  status: string
}

interface Template {
  id: number
  name: string
  content: string
}

// Tabs
const tabs = [
  { id: 'send', label: 'Envoyer SMS' },
  { id: 'opt', label: 'Envoi avancé' },
  { id: 'file', label: 'Import fichier' }
]
const activeTab = ref('send')

// Form data
const channel = ref('transactional')
const route = ref('auto')
const senderId = ref('JOBSSMS')
const recipients = ref('')
const message = ref('')
const sending = ref(false)

// Advanced options
const options = ref({
  unicode: false,
  flash: false,
  dlr: true,
  priority: false
})

// File upload
const fileInput = ref<HTMLInputElement | null>(null)
const isDragging = ref(false)
const uploadedFile = ref<{ name: string; rows: number; columns: string[] } | null>(null)
const columnMapping = ref({ phone: '', name: '' })

// Modals
const showScheduleModal = ref(false)
const showContactPicker = ref(false)
const showGroupPicker = ref(false)
const showTemplates = ref(false)

// Schedule
const scheduleDate = ref('')
const scheduleTime = ref('')
const today = new Date().toISOString().split('T')[0]

// Contacts
const contacts = ref<Contact[]>([])
const contactSearch = ref('')
const selectedContacts = ref<string[]>([])

// Templates
const templates = ref<Template[]>([])
const recentTemplates = computed(() => templates.value.slice(0, 3))

// Computed
const recipientList = computed(() => {
  return recipients.value
    .split(/[\n,]/)
    .map(r => r.trim())
    .filter(r => r.length > 0)
})

const recipientCount = computed(() => recipientList.value.length)

const operatorStats = computed(() => {
  const stats = { airtel: 0, moov: 0, invalid: 0 }
  recipientList.value.forEach(phone => {
    const cleaned = phone.replace(/\D/g, '').slice(-8)
    if (cleaned.length !== 8) {
      stats.invalid++
      return
    }
    const prefix = cleaned.substring(0, 2)
    if (['77', '74', '76'].includes(prefix)) stats.airtel++
    else if (['60', '62', '65', '66'].includes(prefix)) stats.moov++
    else stats.invalid++
  })
  return stats
})

const messageLength = computed(() => message.value.length)
const smsCount = computed(() => Math.ceil(messageLength.value / 160) || 1)
const totalSms = computed(() => recipientCount.value * smsCount.value)
const estimatedCost = computed(() => totalSms.value * 20)

const canSend = computed(() => {
  if (activeTab.value === 'file') {
    return uploadedFile.value && message.value.trim().length > 0
  }
  return recipientCount.value > 0 && message.value.trim().length > 0
})

const filteredContacts = computed(() => {
  if (!contactSearch.value) return contacts.value
  const search = contactSearch.value.toLowerCase()
  return contacts.value.filter(
    c => c.name.toLowerCase().includes(search) || c.phone.includes(search)
  )
})

// Methods
async function loadContacts() {
  try {
    const data = await contactService.getAll()
    contacts.value = data.filter((c: Contact) => c.status === 'active')
  } catch (error) {
    console.error('Error loading contacts:', error)
  }
}

async function loadTemplates() {
  try {
    const response = await apiClient.get('/templates')
    templates.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Error loading templates:', error)
  }
}

function useTemplate(template: Template) {
  message.value = template.content
}

function addSelectedContacts() {
  const currentRecipients = new Set(recipientList.value)
  selectedContacts.value.forEach(phone => currentRecipients.add(phone))
  recipients.value = Array.from(currentRecipients).join('\n')
  selectedContacts.value = []
  showContactPicker = false
}

function handleFileDrop(e: DragEvent) {
  isDragging.value = false
  const files = e.dataTransfer?.files
  if (files && files.length > 0) {
    processFile(files[0])
  }
}

function handleFileSelect(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    processFile(target.files[0])
  }
}

function processFile(file: File) {
  // Simplified file processing - in real implementation, parse CSV/Excel
  uploadedFile.value = {
    name: file.name,
    rows: 100, // Placeholder
    columns: ['phone', 'name', 'email'] // Placeholder
  }
  columnMapping.value = { phone: 'phone', name: 'name' }
}

async function sendNow() {
  if (!canSend.value) return

  const confirmed = await showConfirm(
    'Confirmer l\'envoi',
    `Envoyer ${totalSms.value} SMS a ${recipientCount.value} destinataire(s) pour ${estimatedCost.value} XAF ?`
  )

  if (!confirmed) return

  sending.value = true

  try {
    const data = {
      recipients: recipientList.value,
      message: message.value,
      type: 'immediate',
      channel: channel.value,
      route: route.value,
      sender_id: senderId.value,
      options: options.value
    }

    await apiClient.post('/messages/send', data)

    showSuccess(`${totalSms.value} SMS envoye(s) avec succes !`)
    resetForm()
  } catch (error: any) {
    console.error('Error sending message:', error)
    showError(error.response?.data?.message || 'Erreur lors de l\'envoi')
  } finally {
    sending.value = false
  }
}

async function scheduleMessage() {
  if (!scheduleDate.value || !scheduleTime.value) return

  const scheduledAt = `${scheduleDate.value} ${scheduleTime.value}`

  try {
    await apiClient.post('/messages/send', {
      recipients: recipientList.value,
      message: message.value,
      type: 'scheduled',
      scheduled_at: scheduledAt,
      channel: channel.value,
      route: route.value,
      sender_id: senderId.value
    })

    showSuccess('Message planifie avec succes !')
    showScheduleModal.value = false
    resetForm()
  } catch (error: any) {
    showError(error.response?.data?.message || 'Erreur lors de la planification')
  }
}

async function saveDraft() {
  try {
    await apiClient.post('/templates', {
      name: `Brouillon ${new Date().toLocaleString()}`,
      content: message.value,
      is_public: false
    })
    showSuccess('Brouillon sauvegarde')
    loadTemplates()
  } catch (error: any) {
    showError(error.response?.data?.message || 'Erreur lors de la sauvegarde')
  }
}

function resetForm() {
  recipients.value = ''
  message.value = ''
  uploadedFile.value = null
  scheduleDate.value = ''
  scheduleTime.value = ''
}

onMounted(() => {
  loadContacts()
  loadTemplates()
})
</script>
