<template>
  <MainLayout>
    <div class="p-4 sm:p-6 lg:p-8">
      <!-- Header responsive -->
      <div class="mb-4 sm:mb-6 lg:mb-8">
        <div class="flex flex-col gap-4">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold">Contacts</h1>
            <p class="text-sm text-muted-foreground mt-1 sm:mt-2">Gérez votre liste de contacts</p>
          </div>
          <!-- Buttons - wrap on mobile -->
          <div class="flex flex-wrap gap-2">
            <button
              v-if="hasSelectedContacts"
              @click="openGroupModal"
              class="inline-flex items-center justify-center gap-1 sm:gap-2 whitespace-nowrap rounded-md text-xs sm:text-sm font-medium transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 sm:h-10 px-3 sm:px-4 py-2"
            >
              <UserGroupIcon class="w-4 h-4" />
              <span class="hidden sm:inline">Ajouter à un groupe</span>
              <span class="sm:hidden">Groupe</span>
              <span>({{ selectedContactIds.length }})</span>
            </button>
            <button
              @click="exportContacts"
              :disabled="exporting"
              class="inline-flex items-center justify-center gap-1 sm:gap-2 whitespace-nowrap rounded-md text-xs sm:text-sm font-medium transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 sm:h-10 px-3 sm:px-4 py-2 disabled:opacity-50"
            >
              <ArrowUpTrayIcon v-if="!exporting" class="w-4 h-4" />
              <div v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary"></div>
              <span class="hidden sm:inline">Exporter</span>
            </button>
            <button
              @click="showImportModal = true"
              class="inline-flex items-center justify-center gap-1 sm:gap-2 whitespace-nowrap rounded-md text-xs sm:text-sm font-medium transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 sm:h-10 px-3 sm:px-4 py-2"
            >
              <ArrowDownTrayIcon class="w-4 h-4" />
              <span class="hidden sm:inline">Importer</span>
            </button>
            <button
              @click="openAddModal"
              class="inline-flex items-center justify-center gap-1 sm:gap-2 whitespace-nowrap rounded-md text-xs sm:text-sm font-medium transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-9 sm:h-10 px-3 sm:px-4 py-2"
            >
              <PlusIcon class="w-4 h-4" />
              <span class="hidden sm:inline">Nouveau contact</span>
              <span class="sm:hidden">Nouveau</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Statistiques - 2x2 sur mobile, 4 colonnes sur desktop -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-6 mb-4 sm:mb-6 lg:mb-8">
        <div class="rounded-lg border bg-card p-3 sm:p-4">
          <div class="text-xs sm:text-sm text-muted-foreground">Total contacts</div>
          <div class="text-xl sm:text-2xl font-bold mt-1">{{ totalContacts }}</div>
        </div>
        <div class="rounded-lg border bg-card p-3 sm:p-4">
          <div class="text-xs sm:text-sm text-muted-foreground">Actifs</div>
          <div class="text-xl sm:text-2xl font-bold mt-1 text-success">{{ activeContacts }}</div>
        </div>
        <div class="rounded-lg border bg-card p-3 sm:p-4">
          <div class="text-xs sm:text-sm text-muted-foreground">Inactifs</div>
          <div class="text-xl sm:text-2xl font-bold mt-1 text-muted-foreground">{{ inactiveContacts }}</div>
        </div>
        <div class="rounded-lg border bg-card p-3 sm:p-4">
          <div class="text-xs sm:text-sm text-muted-foreground">Nouveaux (30j)</div>
          <div class="text-xl sm:text-2xl font-bold mt-1 text-primary">{{ newContacts }}</div>
        </div>
      </div>

      <!-- Filtres et recherche - Stack on mobile -->
      <div class="rounded-lg border bg-card shadow-sm mb-4 sm:mb-6 p-3 sm:p-4 lg:p-6">
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
          <div class="flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher..."
              class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-xs sm:text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            />
          </div>
          <select
            v-model="filterStatus"
            class="flex h-9 sm:h-10 rounded-md border border-input bg-background px-3 py-2 text-xs sm:text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
          >
            <option value="all">Tous les statuts</option>
            <option value="active">Actifs</option>
            <option value="inactive">Inactifs</option>
          </select>
        </div>
      </div>

      <!-- Liste des contacts -->
      <div class="rounded-lg border bg-card shadow-sm">
        <div v-if="loading" class="flex items-center justify-center py-8 sm:py-12">
          <div class="animate-spin rounded-full h-10 w-10 sm:h-12 sm:w-12 border-b-2 border-primary"></div>
        </div>
        <div v-else-if="filteredContacts.length === 0" class="flex flex-col items-center justify-center py-8 sm:py-12 px-4">
          <UsersIcon class="w-12 h-12 sm:w-16 sm:h-16 text-muted-foreground mb-3 sm:mb-4" />
          <p class="text-base sm:text-lg font-medium text-center">Aucun contact trouvé</p>
          <p class="text-xs sm:text-sm text-muted-foreground mt-1 text-center">Commencez par ajouter ou importer des contacts</p>
        </div>
        <div v-else>
          <!-- Desktop Table -->
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full">
              <thead class="border-b bg-muted/50">
                <tr>
                  <th class="h-10 sm:h-12 px-3 sm:px-4 text-left align-middle font-medium text-muted-foreground text-xs sm:text-sm">
                    <input type="checkbox" :checked="allSelected" @change="toggleSelectAll" class="rounded border-gray-300 cursor-pointer" />
                  </th>
                  <th class="h-10 sm:h-12 px-3 sm:px-4 text-left align-middle font-medium text-muted-foreground text-xs sm:text-sm">Nom</th>
                  <th class="h-10 sm:h-12 px-3 sm:px-4 text-left align-middle font-medium text-muted-foreground text-xs sm:text-sm">Email</th>
                  <th class="h-10 sm:h-12 px-3 sm:px-4 text-left align-middle font-medium text-muted-foreground text-xs sm:text-sm">Téléphone</th>
                  <th class="h-10 sm:h-12 px-3 sm:px-4 text-left align-middle font-medium text-muted-foreground text-xs sm:text-sm">Statut</th>
                  <th class="h-10 sm:h-12 px-3 sm:px-4 text-left align-middle font-medium text-muted-foreground text-xs sm:text-sm hidden lg:table-cell">Date d'ajout</th>
                  <th class="h-10 sm:h-12 px-3 sm:px-4 text-left align-middle font-medium text-muted-foreground text-xs sm:text-sm">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="contact in filteredContacts" :key="contact.id" class="border-b transition-colors hover:bg-muted/50">
                  <td class="p-3 sm:p-4">
                    <input type="checkbox" :checked="isContactSelected(contact.id)" @change="toggleContact(contact.id)" class="rounded border-gray-300 cursor-pointer" />
                  </td>
                  <td class="p-3 sm:p-4 font-medium text-sm">{{ contact.name }}</td>
                  <td class="p-3 sm:p-4 text-xs sm:text-sm text-muted-foreground truncate max-w-[150px]">{{ contact.email }}</td>
                  <td class="p-3 sm:p-4 text-xs sm:text-sm">{{ contact.phone }}</td>
                  <td class="p-3 sm:p-4">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold" :class="contact.status === 'active' ? 'bg-success/10 text-success' : 'bg-muted text-muted-foreground'">
                      {{ contact.status === 'active' ? 'Actif' : 'Inactif' }}
                    </span>
                  </td>
                  <td class="p-3 sm:p-4 text-xs sm:text-sm text-muted-foreground hidden lg:table-cell">{{ formatDate(contact.created_at) }}</td>
                  <td class="p-3 sm:p-4">
                    <div class="flex gap-1">
                      <button @click="openEditModal(contact)" class="inline-flex items-center justify-center rounded-md hover:bg-accent hover:text-accent-foreground h-8 w-8" title="Modifier">
                        <PencilIcon class="w-4 h-4" />
                      </button>
                      <button @click="deleteContact(contact)" class="inline-flex items-center justify-center rounded-md hover:bg-destructive/10 hover:text-destructive h-8 w-8" title="Supprimer">
                        <TrashIcon class="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile Card Layout -->
          <div class="md:hidden divide-y">
            <div v-for="contact in filteredContacts" :key="contact.id" class="p-3 sm:p-4 hover:bg-muted/30">
              <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 flex-1 min-w-0">
                  <input type="checkbox" :checked="isContactSelected(contact.id)" @change="toggleContact(contact.id)" class="rounded border-gray-300 cursor-pointer mt-1" />
                  <div class="flex-1 min-w-0">
                    <p class="font-medium text-sm truncate">{{ contact.name }}</p>
                    <p class="text-xs text-muted-foreground truncate">{{ contact.phone }}</p>
                    <p class="text-xs text-muted-foreground truncate">{{ contact.email }}</p>
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                      <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold" :class="contact.status === 'active' ? 'bg-success/10 text-success' : 'bg-muted text-muted-foreground'">
                        {{ contact.status === 'active' ? 'Actif' : 'Inactif' }}
                      </span>
                      <span class="text-xs text-muted-foreground">{{ formatDate(contact.created_at) }}</span>
                    </div>
                  </div>
                </div>
                <div class="flex gap-1 flex-shrink-0">
                  <button @click="openEditModal(contact)" class="p-2 hover:bg-accent rounded">
                    <PencilIcon class="w-4 h-4" />
                  </button>
                  <button @click="deleteContact(contact)" class="p-2 hover:bg-destructive/10 text-destructive rounded">
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination responsive -->
      <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-4 sm:mt-6">
        <p class="text-xs sm:text-sm text-muted-foreground text-center sm:text-left">
          {{ filteredContacts.length }} sur {{ contacts.length }} contacts
        </p>
        <div class="flex gap-2">
          <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-xs sm:text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 sm:h-9 px-2 sm:px-3">
            Précédent
          </button>
          <button class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-xs sm:text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 sm:h-9 px-2 sm:px-3">
            Suivant
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Ajout/Modification Contact -->
    <div
      v-if="showAddModal || showEditModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeContactModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-sm sm:max-w-md p-4 sm:p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold">{{ editingContact ? 'Modifier le contact' : 'Nouveau contact' }}</h2>
          <button @click="closeContactModal" class="hover:bg-accent rounded-full p-1">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="saveContact" class="space-y-4">
          <div class="space-y-2">
            <label class="text-sm font-medium">Nom complet *</label>
            <input
              v-model="contactForm.name"
              type="text"
              required
              placeholder="Jean Dupont"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">Email *</label>
            <input
              v-model="contactForm.email"
              type="email"
              required
              placeholder="jean.dupont@example.com"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">Téléphone *</label>
            <input
              v-model="contactForm.phone"
              type="tel"
              required
              placeholder="+237600000000"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">Statut</label>
            <select
              v-model="contactForm.status"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            >
              <option value="active">Actif</option>
              <option value="inactive">Inactif</option>
            </select>
          </div>

          <!-- Custom Fields Section -->
          <div class="space-y-3 pt-4 border-t">
            <div class="flex items-center justify-between">
              <label class="text-sm font-medium">Champs personnalisés</label>
              <TagIcon class="w-4 h-4 text-muted-foreground" />
            </div>

            <!-- Existing Custom Fields -->
            <div v-if="Object.keys(contactForm.custom_fields).length > 0" class="space-y-2">
              <div
                v-for="(value, key) in contactForm.custom_fields"
                :key="key"
                class="flex items-center gap-2 p-2 bg-muted/50 rounded border"
              >
                <div class="flex-1">
                  <p class="text-xs font-medium text-muted-foreground">{{ key }}</p>
                  <p class="text-sm">{{ value }}</p>
                </div>
                <button
                  type="button"
                  @click="removeCustomField(key as string)"
                  class="text-destructive hover:bg-destructive/10 rounded p-1"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- Add New Custom Field -->
            <div class="space-y-2 p-3 bg-muted/30 rounded-lg">
              <p class="text-xs text-muted-foreground">Ajouter un nouveau champ</p>
              <div class="grid grid-cols-2 gap-2">
                <input
                  v-model="customFieldKey"
                  type="text"
                  placeholder="Nom du champ"
                  class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
                <input
                  v-model="customFieldValue"
                  type="text"
                  placeholder="Valeur"
                  class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
              </div>
              <button
                type="button"
                @click="addCustomField"
                class="w-full inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9"
              >
                <PlusIcon class="w-4 h-4" />
                <span>Ajouter</span>
              </button>
            </div>
          </div>

          <div class="flex gap-3 pt-4 border-t">
            <button
              type="button"
              @click="closeContactModal"
              class="flex-1 inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="flex-1 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
            >
              <div v-if="saving" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              <span>{{ saving ? 'Enregistrement...' : (editingContact ? 'Modifier' : 'Ajouter') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Import CSV -->
    <div
      v-if="showImportModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeImportModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-sm sm:max-w-2xl lg:max-w-4xl max-h-[90vh] overflow-y-auto p-4 sm:p-6">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-xl font-bold">Importer des contacts</h2>
            <p class="text-sm text-muted-foreground mt-1">Importez vos contacts depuis un fichier CSV, Excel (.xlsx) ou .xls</p>
          </div>
          <button @click="closeImportModal" class="hover:bg-accent rounded-full p-1">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Step 1: Upload File -->
        <div v-if="importStep === 1" class="space-y-6">
          <div class="border-2 border-dashed rounded-lg p-8 text-center">
            <DocumentArrowUpIcon class="w-16 h-16 mx-auto text-muted-foreground mb-4" />
            <div class="space-y-2">
              <p class="text-sm font-medium">Glissez-déposez votre fichier ici</p>
              <p class="text-xs text-muted-foreground">CSV, Excel (.xlsx, .xls) - Max 20MB</p>
            </div>
            <input
              type="file"
              accept=".csv,.xlsx,.xls"
              @change="handleFileUpload"
              class="hidden"
              ref="fileInput"
            />
            <button
              @click="($refs.fileInput as HTMLInputElement)?.click()"
              class="mt-4 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
            >
              Sélectionner un fichier
            </button>
          </div>

          <div v-if="importFile" class="p-4 bg-muted rounded-lg">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <DocumentTextIcon class="w-8 h-8 text-primary" />
                <div>
                  <p class="font-medium">{{ importFile.name }}</p>
                  <p class="text-xs text-muted-foreground">{{ formatFileSize(importFile.size) }}</p>
                </div>
              </div>
              <button @click="importFile = null" class="text-destructive hover:bg-destructive/10 rounded p-2">
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Duplicate Handling Option -->
          <div class="space-y-3">
            <label class="text-sm font-medium">Gestion des doublons</label>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <label
                class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer transition-colors"
                :class="duplicateAction === 'skip' ? 'border-primary bg-primary/5' : 'hover:bg-accent'"
              >
                <input type="radio" v-model="duplicateAction" value="skip" class="mt-1" />
                <div>
                  <p class="font-medium text-sm">Ignorer</p>
                  <p class="text-xs text-muted-foreground">Les doublons sont ignorés</p>
                </div>
              </label>
              <label
                class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer transition-colors"
                :class="duplicateAction === 'update' ? 'border-primary bg-primary/5' : 'hover:bg-accent'"
              >
                <input type="radio" v-model="duplicateAction" value="update" class="mt-1" />
                <div>
                  <p class="font-medium text-sm">Mettre à jour</p>
                  <p class="text-xs text-muted-foreground">Les doublons sont mis à jour</p>
                </div>
              </label>
              <label
                class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer transition-colors"
                :class="duplicateAction === 'create' ? 'border-primary bg-primary/5' : 'hover:bg-accent'"
              >
                <input type="radio" v-model="duplicateAction" value="create" class="mt-1" />
                <div>
                  <p class="font-medium text-sm">Créer</p>
                  <p class="text-xs text-muted-foreground">Créer même si doublon</p>
                </div>
              </label>
            </div>
          </div>

          <div class="p-4 bg-primary/10 rounded-lg border border-primary/20">
            <div class="flex items-start gap-3">
              <InformationCircleIcon class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
              <div class="text-sm">
                <p class="font-medium mb-2">Formats supportés :</p>
                <ul class="space-y-1 text-muted-foreground">
                  <li>• <strong>CSV</strong> : Séparateur virgule, encodage UTF-8</li>
                  <li>• <strong>Excel</strong> : Formats .xlsx et .xls</li>
                  <li>• La première ligne doit contenir les en-têtes</li>
                  <li>• Colonnes requises : téléphone (obligatoire), nom, email</li>
                </ul>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t">
            <button
              @click="closeImportModal"
              class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
            >
              Annuler
            </button>
            <button
              @click="parseFile"
              :disabled="!importFile || parsing"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
            >
              <div v-if="parsing" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              <span>{{ parsing ? 'Analyse...' : 'Continuer' }}</span>
            </button>
          </div>
        </div>

        <!-- Step 2: Column Mapping -->
        <div v-if="importStep === 2" class="space-y-6">
          <div class="p-4 bg-muted/50 rounded-lg">
            <p class="text-sm font-medium mb-2">Mappage des colonnes</p>
            <p class="text-xs text-muted-foreground">
              Associez les colonnes de votre fichier CSV aux champs du système. {{ csvPreview.length }} lignes détectées.
            </p>
          </div>

          <div class="space-y-4">
            <div v-for="(column, index) in csvHeaders" :key="index" class="grid grid-cols-2 gap-4 items-center">
              <div class="p-3 bg-muted rounded-lg">
                <p class="text-xs text-muted-foreground mb-1">Colonne CSV</p>
                <p class="font-medium">{{ column }}</p>
                <p class="text-xs text-muted-foreground mt-1">Exemple: {{ csvPreview[0]?.[index] || '-' }}</p>
              </div>
              <div class="space-y-2">
                <label class="text-xs text-muted-foreground">Associer à</label>
                <select
                  v-model="columnMapping[column]"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                >
                  <option value="">-- Ne pas importer --</option>
                  <option value="name">Nom</option>
                  <option value="email">Email</option>
                  <option value="phone">Téléphone</option>
                  <option value="group">Groupe</option>
                  <option value="status">Statut</option>
                </select>
              </div>
            </div>
          </div>

          <div class="p-4 bg-warning/10 rounded-lg border border-warning/20">
            <div class="flex items-start gap-3">
              <ExclamationTriangleIcon class="w-5 h-5 text-warning flex-shrink-0 mt-0.5" />
              <div class="text-sm">
                <p class="font-medium mb-1">Champ obligatoire</p>
                <p class="text-muted-foreground">Le champ Téléphone est obligatoire pour chaque contact. Les doublons sont détectés par téléphone ou email.</p>
              </div>
            </div>
          </div>

          <div class="flex justify-between gap-3 pt-4 border-t">
            <button
              @click="importStep = 1"
              class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
            >
              Retour
            </button>
            <button
              @click="importStep = 3"
              :disabled="!isMappingValid"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
            >
              Continuer
            </button>
          </div>
        </div>

        <!-- Step 3: Preview & Import -->
        <div v-if="importStep === 3" class="space-y-6">
          <div class="p-4 bg-muted/50 rounded-lg">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium mb-1">Prêt à importer</p>
                <p class="text-xs text-muted-foreground">
                  {{ totalRows }} lignes détectées dans le fichier
                </p>
              </div>
              <div class="text-right">
                <p class="text-2xl font-bold text-primary">{{ totalRows }}</p>
                <p class="text-xs text-muted-foreground">contacts</p>
              </div>
            </div>
          </div>

          <!-- Preview table -->
          <div class="rounded-lg border overflow-hidden max-h-64 overflow-y-auto">
            <table class="w-full">
              <thead class="border-b bg-muted/50 sticky top-0">
                <tr>
                  <th v-for="header in importHeaders" :key="header" class="h-10 px-4 text-left align-middle font-medium text-muted-foreground text-xs">
                    {{ header }}
                    <span v-if="columnMapping[header]" class="text-primary">({{ getFieldLabel(columnMapping[header]) }})</span>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, index) in importPreview.slice(0, 5)" :key="index" class="border-b">
                  <td v-for="(cell, cellIndex) in row" :key="cellIndex" class="p-3 text-sm truncate max-w-[150px]">
                    {{ cell || '-' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="totalRows > 5" class="text-center text-sm text-muted-foreground">
            Aperçu des 5 premières lignes sur {{ totalRows }}
          </div>

          <!-- Import settings summary -->
          <div class="p-4 bg-primary/5 rounded-lg border">
            <p class="text-sm font-medium mb-2">Récapitulatif</p>
            <div class="grid grid-cols-2 gap-2 text-sm">
              <div class="text-muted-foreground">Fichier :</div>
              <div class="font-medium">{{ importFile?.name }}</div>
              <div class="text-muted-foreground">Gestion doublons :</div>
              <div class="font-medium">{{ getDuplicateActionLabel(duplicateAction) }}</div>
              <div class="text-muted-foreground">Colonnes mappées :</div>
              <div class="font-medium">{{ Object.values(columnMapping).filter(v => v).length }}</div>
            </div>
          </div>

          <div class="flex justify-between gap-3 pt-4 border-t">
            <button
              @click="importStep = 2"
              class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
            >
              Retour
            </button>
            <button
              @click="importContacts"
              :disabled="importing || totalRows === 0"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-success text-success-foreground hover:bg-success/90 h-10 px-4 py-2"
            >
              <div v-if="importing" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              <ArrowDownTrayIcon v-else class="w-4 h-4" />
              <span>{{ importing ? 'Importation...' : `Importer ${totalRows} contacts` }}</span>
            </button>
          </div>
        </div>

        <!-- Step 4: Results -->
        <div v-if="importStep === 4" class="space-y-6">
          <div class="text-center py-6">
            <div v-if="importResults" class="space-y-4">
              <CheckCircleIcon class="w-16 h-16 mx-auto text-success" />
              <h3 class="text-xl font-bold">Importation terminée</h3>

              <div class="grid grid-cols-3 gap-4 max-w-md mx-auto">
                <div class="p-4 bg-success/10 rounded-lg">
                  <p class="text-2xl font-bold text-success">{{ importResults.imported }}</p>
                  <p class="text-xs text-muted-foreground">Importés</p>
                </div>
                <div class="p-4 bg-primary/10 rounded-lg">
                  <p class="text-2xl font-bold text-primary">{{ importResults.updated }}</p>
                  <p class="text-xs text-muted-foreground">Mis à jour</p>
                </div>
                <div class="p-4 bg-muted rounded-lg">
                  <p class="text-2xl font-bold">{{ importResults.skipped }}</p>
                  <p class="text-xs text-muted-foreground">Ignorés</p>
                </div>
              </div>

              <!-- Errors if any -->
              <div v-if="importResults.errors.length > 0" class="mt-4 text-left max-h-40 overflow-y-auto">
                <div class="p-4 bg-destructive/10 rounded-lg border border-destructive/20">
                  <p class="text-sm font-medium text-destructive mb-2">
                    {{ importResults.total_errors }} erreur(s) rencontrée(s)
                  </p>
                  <ul class="text-xs text-muted-foreground space-y-1">
                    <li v-for="(err, i) in importResults.errors.slice(0, 10)" :key="i">{{ err }}</li>
                  </ul>
                  <p v-if="importResults.total_errors > 10" class="text-xs text-muted-foreground mt-2">
                    ... et {{ importResults.total_errors - 10 }} autres erreurs
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div class="flex justify-center gap-3 pt-4 border-t">
            <button
              @click="closeImportModal"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-6 py-2"
            >
              Fermer
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Ajouter à un Groupe -->
    <div
      v-if="showGroupModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeGroupModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-sm sm:max-w-md">
        <div class="border-b p-6">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">Ajouter à un groupe</h2>
            <button @click="closeGroupModal" class="hover:bg-accent rounded-full p-1">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
          <p class="text-sm text-muted-foreground mt-2">
            {{ selectedContactIds.length }} contact(s) sélectionné(s)
          </p>
        </div>

        <div class="p-6 space-y-4">
          <div v-if="groups.length === 0" class="text-center py-6">
            <UserGroupIcon class="w-12 h-12 text-muted-foreground mx-auto mb-3" />
            <p class="text-sm text-muted-foreground">Aucun groupe disponible</p>
            <p class="text-xs text-muted-foreground mt-1">Créez un groupe depuis la page Groupes</p>
          </div>

          <div v-else class="space-y-2">
            <label class="text-sm font-medium">Sélectionner un groupe *</label>
            <select
              v-model="selectedGroupId"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            >
              <option :value="null">-- Choisir un groupe --</option>
              <option v-for="group in groups" :key="group.id" :value="group.id">
                {{ group.name }} ({{ group.contacts_count }} contacts)
              </option>
            </select>
          </div>

          <div class="flex gap-3 pt-4 border-t">
            <button
              type="button"
              @click="closeGroupModal"
              class="flex-1 inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
            >
              Annuler
            </button>
            <button
              type="button"
              @click="addContactsToGroup"
              :disabled="!selectedGroupId || addingToGroup || groups.length === 0"
              class="flex-1 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
            >
              <div v-if="addingToGroup" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              <span>{{ addingToGroup ? 'Ajout...' : 'Ajouter' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import MainLayout from '@/components/MainLayout.vue'
import {
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
  XMarkIcon,
  DocumentArrowUpIcon,
  DocumentTextIcon,
  InformationCircleIcon,
  ExclamationTriangleIcon,
  UsersIcon,
  UserGroupIcon,
  TagIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline'
import { contactService, type Contact } from '@/services/contactService'
import { showSuccess, showError, showConfirm } from '@/utils/notifications'
import api from '@/services/api'

interface ContactGroup {
  id: number
  name: string
  description: string | null
  contacts_count: number
  created_at: string
  updated_at: string
}

const searchQuery = ref('')
const filterStatus = ref('all')
const showAddModal = ref(false)
const showEditModal = ref(false)
const showImportModal = ref(false)
const showGroupModal = ref(false)
const contacts = ref<Contact[]>([])
const groups = ref<ContactGroup[]>([])
const selectedContactIds = ref<number[]>([])
const selectedGroupId = ref<number | null>(null)
const loading = ref(false)
const saving = ref(false)
const exporting = ref(false)
const error = ref('')
const addingToGroup = ref(false)

// Contact Form
const contactForm = ref({
  name: '',
  email: '',
  phone: '',
  status: 'active' as 'active' | 'inactive',
  custom_fields: {} as Record<string, any>
})
const editingContact = ref<Contact | null>(null)
const customFieldKey = ref('')
const customFieldValue = ref('')

// File Import (CSV, XLSX, XLS)
const importStep = ref(1)
const importFile = ref<File | null>(null)
const importHeaders = ref<string[]>([])
const importPreview = ref<any[][]>([])
const columnMapping = ref<Record<string, string>>({})
const duplicateAction = ref<'skip' | 'update' | 'create'>('skip')
const totalRows = ref(0)
const parsing = ref(false)
const importing = ref(false)
const importProgress = ref(0)
const importResults = ref<{
  imported: number
  updated: number
  skipped: number
  errors: string[]
  total_errors: number
} | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)

// Legacy aliases for template compatibility
const csvFile = importFile
const csvHeaders = importHeaders
const csvPreview = importPreview

const filteredContacts = computed(() => {
  let result = contacts.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(contact =>
      contact.name.toLowerCase().includes(query) ||
      contact.email.toLowerCase().includes(query) ||
      contact.phone.includes(query)
    )
  }

  if (filterStatus.value !== 'all') {
    result = result.filter(contact => contact.status === filterStatus.value)
  }

  return result
})

const totalContacts = computed(() => contacts.value.length)
const activeContacts = computed(() => contacts.value.filter(c => c.status === 'active').length)
const inactiveContacts = computed(() => contacts.value.filter(c => c.status === 'inactive').length)
const newContacts = computed(() => {
  const thirtyDaysAgo = new Date()
  thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)
  return contacts.value.filter(c => {
    const createdAt = new Date(c.created_at || '')
    return createdAt >= thirtyDaysAgo
  }).length
})

const isMappingValid = computed(() => {
  const mappedFields = Object.values(columnMapping.value).filter(v => v)
  // Phone is required, name and email are optional
  return mappedFields.includes('phone')
})

// Preview contacts based on mapping (for display purposes only)
const validContacts = computed(() => {
  return importPreview.value.map(row => {
    const contact: any = { status: 'active' }
    importHeaders.value.forEach((header, index) => {
      const field = columnMapping.value[header]
      if (field && row[index]) {
        contact[field] = row[index]
      }
    })
    return contact
  }).filter(c => c.phone)
})

const allSelected = computed(() => {
  return filteredContacts.value.length > 0 &&
    filteredContacts.value.every(c => selectedContactIds.value.includes(c.id))
})

const hasSelectedContacts = computed(() => selectedContactIds.value.length > 0)

function formatDate(dateString?: string): string {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

async function loadContacts() {
  loading.value = true
  error.value = ''
  try {
    const data = await contactService.getAll()
    console.log('Contacts loaded:', data)
    contacts.value = data
  } catch (err: any) {
    error.value = err.message || 'Erreur lors du chargement des contacts'
    console.error('Error loading contacts:', err)
  } finally {
    loading.value = false
  }
}

async function exportContacts() {
  exporting.value = true
  try {
    const blob = await contactService.exportCsv()
    const filename = `contacts_${new Date().toISOString().slice(0, 10)}.csv`
    contactService.downloadFile(blob, filename)
    showSuccess('Contacts exportés avec succès')
  } catch (err: any) {
    console.error('Error exporting contacts:', err)
    showError(err.response?.data?.message || 'Erreur lors de l\'export des contacts')
  } finally {
    exporting.value = false
  }
}

function openAddModal() {
  editingContact.value = null
  contactForm.value = {
    name: '',
    email: '',
    phone: '',
    status: 'active',
    custom_fields: {}
  }
  customFieldKey.value = ''
  customFieldValue.value = ''
  showAddModal.value = true
}

function openEditModal(contact: Contact) {
  editingContact.value = contact
  contactForm.value = {
    name: contact.name,
    email: contact.email,
    phone: contact.phone,
    status: contact.status,
    custom_fields: contact.custom_fields || {}
  }
  customFieldKey.value = ''
  customFieldValue.value = ''
  showEditModal.value = true
}

function addCustomField() {
  if (!customFieldKey.value.trim()) {
    showError('Le nom du champ est requis')
    return
  }
  contactForm.value.custom_fields[customFieldKey.value] = customFieldValue.value
  customFieldKey.value = ''
  customFieldValue.value = ''
}

function removeCustomField(key: string) {
  delete contactForm.value.custom_fields[key]
}

function closeContactModal() {
  showAddModal.value = false
  showEditModal.value = false
  editingContact.value = null
}

async function saveContact() {
  saving.value = true
  try {
    if (editingContact.value) {
      await contactService.update(editingContact.value.id, contactForm.value)
      showSuccess('Contact modifié avec succès')
    } else {
      await contactService.create(contactForm.value)
      showSuccess('Contact ajouté avec succès')
    }
    closeContactModal()
    // Reload the entire list to ensure consistency
    await loadContacts()
  } catch (err: any) {
    showError(err.response?.data?.message || err.message || 'Erreur lors de l\'enregistrement')
  } finally {
    saving.value = false
  }
}

async function deleteContact(contact: Contact) {
  const confirmed = await showConfirm(
    'Supprimer le contact ?',
    `Êtes-vous sûr de vouloir supprimer ${contact.name} ?`
  )

  if (confirmed) {
    try {
      await contactService.delete(contact.id)
      showSuccess('Contact supprimé avec succès')
      contacts.value = contacts.value.filter(c => c.id !== contact.id)
    } catch (err: any) {
      showError(err.message || 'Erreur lors de la suppression')
      console.error('Error deleting contact:', err)
    }
  }
}

function closeImportModal() {
  showImportModal.value = false
  importStep.value = 1
  importFile.value = null
  importHeaders.value = []
  importPreview.value = []
  columnMapping.value = {}
  duplicateAction.value = 'skip'
  totalRows.value = 0
  importResults.value = null
  importProgress.value = 0
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB'
}

function getFieldLabel(field: string): string {
  const labels: Record<string, string> = {
    name: 'Nom',
    email: 'Email',
    phone: 'Téléphone',
    group: 'Groupe',
    status: 'Statut'
  }
  return labels[field] || field
}

function getDuplicateActionLabel(action: string): string {
  const labels: Record<string, string> = {
    skip: 'Ignorer les doublons',
    update: 'Mettre à jour les doublons',
    create: 'Créer malgré les doublons'
  }
  return labels[action] || action
}

function handleFileUpload(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    importFile.value = target.files[0]
  }
}

async function parseFile() {
  if (!importFile.value) return

  parsing.value = true
  try {
    const formData = new FormData()
    formData.append('file', importFile.value)

    const response = await api.post('/contacts/preview-import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    if (!response.data.success) {
      showError(response.data.message || 'Erreur lors de l\'analyse du fichier')
      parsing.value = false
      return
    }

    // Set headers and preview from API response
    importHeaders.value = response.data.headers
    importPreview.value = response.data.preview
    totalRows.value = response.data.total_rows

    // Apply suggested mapping from API
    columnMapping.value = response.data.suggested_mapping || {}

    importStep.value = 2
  } catch (err: any) {
    showError('Erreur lors de la lecture du fichier: ' + (err.response?.data?.message || err.message))
  } finally {
    parsing.value = false
  }
}

// Keep old function name as alias for compatibility
const parseCSV = parseFile

async function importContacts() {
  if (!importFile.value || totalRows.value === 0) return

  importing.value = true
  try {
    const formData = new FormData()
    formData.append('file', importFile.value)
    formData.append('duplicate_action', duplicateAction.value)

    // Send column mapping as JSON
    for (const [key, value] of Object.entries(columnMapping.value)) {
      if (value) {
        formData.append(`column_mapping[${key}]`, value)
      }
    }

    const response = await api.post('/contacts/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    if (response.data.success) {
      importResults.value = {
        imported: response.data.imported || 0,
        updated: response.data.updated || 0,
        skipped: response.data.skipped || 0,
        errors: response.data.errors || [],
        total_errors: response.data.total_errors || 0
      }

      // Show step 4 with results
      importStep.value = 4

      // Reload contacts
      await loadContacts()

      showSuccess(response.data.message)
    } else {
      showError(response.data.message || 'Erreur lors de l\'importation')
    }
  } catch (err: any) {
    showError(err.response?.data?.message || err.message || 'Erreur lors de l\'importation')
  } finally {
    importing.value = false
  }
}

function toggleSelectAll() {
  if (allSelected.value) {
    selectedContactIds.value = []
  } else {
    selectedContactIds.value = filteredContacts.value.map(c => c.id)
  }
}

function toggleContact(contactId: number) {
  const index = selectedContactIds.value.indexOf(contactId)
  if (index > -1) {
    selectedContactIds.value.splice(index, 1)
  } else {
    selectedContactIds.value.push(contactId)
  }
}

function isContactSelected(contactId: number): boolean {
  return selectedContactIds.value.includes(contactId)
}

async function loadGroups() {
  try {
    const response = await api.get('/contact-groups')
    groups.value = response.data.data
  } catch (err: any) {
    console.error('Error loading groups:', err)
  }
}

function openGroupModal() {
  if (selectedContactIds.value.length === 0) {
    showError('Veuillez sélectionner au moins un contact')
    return
  }
  selectedGroupId.value = null
  showGroupModal.value = true
}

function closeGroupModal() {
  showGroupModal.value = false
  selectedGroupId.value = null
}

async function addContactsToGroup() {
  if (!selectedGroupId.value) {
    showError('Veuillez sélectionner un groupe')
    return
  }

  addingToGroup.value = true
  try {
    await api.post(`/contact-groups/${selectedGroupId.value}/contacts/add`, {
      contact_ids: selectedContactIds.value
    })
    showSuccess(`${selectedContactIds.value.length} contact(s) ajouté(s) au groupe`)
    selectedContactIds.value = []
    closeGroupModal()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de l\'ajout au groupe')
  } finally {
    addingToGroup.value = false
  }
}

onMounted(() => {
  loadContacts()
  loadGroups()
})
</script>
