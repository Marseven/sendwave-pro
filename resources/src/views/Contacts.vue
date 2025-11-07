<template>
  <MainLayout>
    <div class="p-8">
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold">Contacts</h1>
          <p class="text-muted-foreground mt-2">Gérez votre liste de contacts</p>
        </div>
        <div class="flex gap-3">
          <button
            v-if="hasSelectedContacts"
            @click="openGroupModal"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
          >
            <UserGroupIcon class="w-4 h-4" />
            <span>Ajouter à un groupe ({{ selectedContactIds.length }})</span>
          </button>
          <button
            @click="showImportModal = true"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
          >
            <ArrowDownTrayIcon class="w-4 h-4" />
            <span>Importer</span>
          </button>
          <button
            @click="openAddModal"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
          >
            <PlusIcon class="w-4 h-4" />
            <span>Nouveau contact</span>
          </button>
        </div>
      </div>

      <!-- Statistiques -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="rounded-lg border bg-card p-4">
          <div class="text-sm text-muted-foreground">Total contacts</div>
          <div class="text-2xl font-bold mt-1">{{ totalContacts }}</div>
        </div>
        <div class="rounded-lg border bg-card p-4">
          <div class="text-sm text-muted-foreground">Actifs</div>
          <div class="text-2xl font-bold mt-1 text-success">{{ activeContacts }}</div>
        </div>
        <div class="rounded-lg border bg-card p-4">
          <div class="text-sm text-muted-foreground">Inactifs</div>
          <div class="text-2xl font-bold mt-1 text-muted-foreground">{{ inactiveContacts }}</div>
        </div>
        <div class="rounded-lg border bg-card p-4">
          <div class="text-sm text-muted-foreground">Nouveaux (30j)</div>
          <div class="text-2xl font-bold mt-1 text-primary">{{ newContacts }}</div>
        </div>
      </div>

      <!-- Filtres et recherche -->
      <div class="rounded-lg border bg-card shadow-sm mb-6 p-6">
        <div class="flex gap-4">
          <div class="flex-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher par nom, email ou téléphone..."
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            />
          </div>
          <select
            v-model="filterStatus"
            class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
          >
            <option value="all">Tous les statuts</option>
            <option value="active">Actifs</option>
            <option value="inactive">Inactifs</option>
          </select>
        </div>
      </div>

      <!-- Liste des contacts -->
      <div class="rounded-lg border bg-card shadow-sm">
        <div v-if="loading" class="flex items-center justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
        </div>
        <div v-else-if="filteredContacts.length === 0" class="flex flex-col items-center justify-center py-12">
          <UsersIcon class="w-16 h-16 text-muted-foreground mb-4" />
          <p class="text-lg font-medium">Aucun contact trouvé</p>
          <p class="text-sm text-muted-foreground mt-1">Commencez par ajouter ou importer des contacts</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="border-b bg-muted/50">
              <tr>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                  <input
                    type="checkbox"
                    :checked="allSelected"
                    @change="toggleSelectAll"
                    class="rounded border-gray-300 cursor-pointer"
                  />
                </th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Nom</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Email</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Téléphone</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Statut</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Date d'ajout</th>
                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="contact in filteredContacts"
                :key="contact.id"
                class="border-b transition-colors hover:bg-muted/50"
              >
                <td class="p-4">
                  <input
                    type="checkbox"
                    :checked="isContactSelected(contact.id)"
                    @change="toggleContact(contact.id)"
                    class="rounded border-gray-300 cursor-pointer"
                  />
                </td>
                <td class="p-4 font-medium">{{ contact.name }}</td>
                <td class="p-4 text-sm text-muted-foreground">{{ contact.email }}</td>
                <td class="p-4 text-sm">{{ contact.phone }}</td>
                <td class="p-4">
                  <span
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                    :class="contact.status === 'active' ? 'bg-success/10 text-success' : 'bg-muted text-muted-foreground'"
                  >
                    {{ contact.status === 'active' ? 'Actif' : 'Inactif' }}
                  </span>
                </td>
                <td class="p-4 text-sm text-muted-foreground">{{ formatDate(contact.created_at) }}</td>
                <td class="p-4">
                  <div class="flex gap-2">
                    <button
                      @click="openEditModal(contact)"
                      class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground h-8 w-8"
                      title="Modifier"
                    >
                      <PencilIcon class="w-4 h-4" />
                    </button>
                    <button
                      @click="deleteContact(contact)"
                      class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:bg-destructive/10 hover:text-destructive h-8 w-8"
                      title="Supprimer"
                    >
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div class="flex items-center justify-between mt-6">
        <p class="text-sm text-muted-foreground">
          Affichage de 1 à {{ filteredContacts.length }} sur {{ contacts.length }} contacts
        </p>
        <div class="flex gap-2">
          <button
            class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
          >
            Précédent
          </button>
          <button
            class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
          >
            Suivant
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Ajout/Modification Contact -->
    <div
      v-if="showAddModal || showEditModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="closeContactModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-md p-6">
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
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="closeImportModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto p-6">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-xl font-bold">Importer des contacts</h2>
            <p class="text-sm text-muted-foreground mt-1">Importez vos contacts depuis un fichier CSV</p>
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
              <p class="text-sm font-medium">Glissez-déposez votre fichier CSV ici</p>
              <p class="text-xs text-muted-foreground">ou cliquez pour sélectionner un fichier</p>
            </div>
            <input
              type="file"
              accept=".csv"
              @change="handleFileUpload"
              class="hidden"
              ref="fileInput"
            />
            <button
              @click="$refs.fileInput.click()"
              class="mt-4 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
            >
              Sélectionner un fichier
            </button>
          </div>

          <div v-if="csvFile" class="p-4 bg-muted rounded-lg">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <DocumentTextIcon class="w-8 h-8 text-primary" />
                <div>
                  <p class="font-medium">{{ csvFile.name }}</p>
                  <p class="text-xs text-muted-foreground">{{ (csvFile.size / 1024).toFixed(2) }} KB</p>
                </div>
              </div>
              <button @click="csvFile = null" class="text-destructive hover:bg-destructive/10 rounded p-2">
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div class="p-4 bg-primary/10 rounded-lg border border-primary/20">
            <div class="flex items-start gap-3">
              <InformationCircleIcon class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" />
              <div class="text-sm">
                <p class="font-medium mb-2">Format du fichier CSV :</p>
                <ul class="space-y-1 text-muted-foreground">
                  <li>• La première ligne doit contenir les en-têtes de colonnes</li>
                  <li>• Les champs doivent être séparés par des virgules (,)</li>
                  <li>• Format recommandé : nom, email, téléphone</li>
                  <li>• Encodage UTF-8 recommandé</li>
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
              @click="parseCSV"
              :disabled="!csvFile || parsing"
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
                </select>
              </div>
            </div>
          </div>

          <div class="p-4 bg-warning/10 rounded-lg border border-warning/20">
            <div class="flex items-start gap-3">
              <ExclamationTriangleIcon class="w-5 h-5 text-warning flex-shrink-0 mt-0.5" />
              <div class="text-sm">
                <p class="font-medium mb-1">Champs obligatoires</p>
                <p class="text-muted-foreground">Les champs Nom, Email et Téléphone sont obligatoires pour chaque contact.</p>
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
            <p class="text-sm font-medium mb-2">Aperçu des données</p>
            <p class="text-xs text-muted-foreground">
              Vérifiez les données avant l'importation. {{ validContacts.length }} contacts valides sur {{ csvPreview.length }}.
            </p>
          </div>

          <div class="rounded-lg border overflow-hidden max-h-96 overflow-y-auto">
            <table class="w-full">
              <thead class="border-b bg-muted/50 sticky top-0">
                <tr>
                  <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground text-xs">Nom</th>
                  <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground text-xs">Email</th>
                  <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground text-xs">Téléphone</th>
                  <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground text-xs">Statut</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(contact, index) in validContacts.slice(0, 20)"
                  :key="index"
                  class="border-b"
                >
                  <td class="p-3 text-sm">{{ contact.name }}</td>
                  <td class="p-3 text-sm text-muted-foreground">{{ contact.email }}</td>
                  <td class="p-3 text-sm">{{ contact.phone }}</td>
                  <td class="p-3 text-sm">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-success/10 text-success">
                      Valide
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="validContacts.length > 20" class="text-center text-sm text-muted-foreground">
            ... et {{ validContacts.length - 20 }} autres contacts
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
              :disabled="importing || validContacts.length === 0"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-success text-success-foreground hover:bg-success/90 h-10 px-4 py-2"
            >
              <div v-if="importing" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              <ArrowDownTrayIcon v-else class="w-4 h-4" />
              <span>{{ importing ? 'Importation...' : `Importer ${validContacts.length} contacts` }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Ajouter à un Groupe -->
    <div
      v-if="showGroupModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
      @click.self="closeGroupModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-md">
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
  TagIcon
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
const error = ref('')
const addingToGroup = ref(false)

// Contact Form
const contactForm = ref({
  name: '',
  email: '',
  phone: '',
  status: 'active' as 'active' | 'inactive'
})
const editingContact = ref<Contact | null>(null)

// CSV Import
const importStep = ref(1)
const csvFile = ref<File | null>(null)
const csvHeaders = ref<string[]>([])
const csvPreview = ref<string[][]>([])
const columnMapping = ref<Record<string, string>>({})
const parsing = ref(false)
const importing = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

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
  return mappedFields.includes('name') && mappedFields.includes('email') && mappedFields.includes('phone')
})

const validContacts = computed(() => {
  return csvPreview.value.map(row => {
    const contact: any = { status: 'active' }
    csvHeaders.value.forEach((header, index) => {
      const field = columnMapping.value[header]
      if (field) {
        contact[field] = row[index]
      }
    })
    return contact
  }).filter(c => c.name && c.email && c.phone)
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

function openAddModal() {
  editingContact.value = null
  contactForm.value = {
    name: '',
    email: '',
    phone: '',
    status: 'active'
  }
  showAddModal.value = true
}

function openEditModal(contact: Contact) {
  editingContact.value = contact
  contactForm.value = {
    name: contact.name,
    email: contact.email,
    phone: contact.phone,
    status: contact.status
  }
  showEditModal.value = true
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
  csvFile.value = null
  csvHeaders.value = []
  csvPreview.value = []
  columnMapping.value = {}
}

function handleFileUpload(event: Event) {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    csvFile.value = target.files[0]
  }
}

async function parseCSV() {
  if (!csvFile.value) return

  parsing.value = true
  try {
    const text = await csvFile.value.text()
    const lines = text.split('\n').filter(line => line.trim())

    if (lines.length === 0) {
      showError('Le fichier CSV est vide')
      parsing.value = false
      return
    }

    // Parse headers
    csvHeaders.value = lines[0].split(',').map(h => h.trim().replace(/"/g, ''))

    // Parse preview (first 100 rows)
    csvPreview.value = lines.slice(1, 101).map(line => {
      return line.split(',').map(cell => cell.trim().replace(/"/g, ''))
    })

    // Auto-map columns based on common names
    csvHeaders.value.forEach(header => {
      const lower = header.toLowerCase()
      if (lower.includes('nom') || lower.includes('name')) {
        columnMapping.value[header] = 'name'
      } else if (lower.includes('email') || lower.includes('mail')) {
        columnMapping.value[header] = 'email'
      } else if (lower.includes('tel') || lower.includes('phone') || lower.includes('mobile')) {
        columnMapping.value[header] = 'phone'
      }
    })

    importStep.value = 2
  } catch (err: any) {
    showError('Erreur lors de la lecture du fichier: ' + err.message)
  } finally {
    parsing.value = false
  }
}

async function importContacts() {
  if (validContacts.value.length === 0) return

  importing.value = true
  try {
    let successCount = 0
    let errorCount = 0

    for (const contact of validContacts.value) {
      try {
        await contactService.create(contact)
        successCount++
      } catch (err) {
        errorCount++
        console.error('Error importing contact:', err)
      }
    }

    if (errorCount > 0) {
      showSuccess(`Importation terminée: ${successCount} contacts importés, ${errorCount} erreurs`)
    } else {
      showSuccess(`${successCount} contacts importés avec succès !`)
    }
    await loadContacts()
    closeImportModal()
  } catch (err: any) {
    showError(err.message || 'Erreur lors de l\'importation')
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
