<template>
  <MainLayout>
    <div class="p-4 sm:p-6 lg:p-8">
      <!-- Header -->
      <div class="mb-4 sm:mb-6 lg:mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold">Gestion des comptes</h1>
        <p class="text-sm text-muted-foreground mt-1 sm:mt-2">
          Gérez les comptes, utilisateurs et rôles de la plateforme
        </p>
      </div>

      <!-- Tabs -->
      <div class="mb-6 border-b overflow-x-auto">
        <nav class="flex gap-2 sm:gap-4 -mb-px min-w-max">
          <button
            v-if="authStore.isSuperAdmin"
            @click="activeTab = 'accounts'"
            class="py-3 px-2 sm:px-3 text-xs sm:text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
            :class="activeTab === 'accounts' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
          >
            <BuildingOfficeIcon class="w-4 h-4 inline mr-1 sm:mr-2" />
            Comptes
          </button>
          <button
            v-if="authStore.canManageUsers"
            @click="activeTab = 'users'"
            class="py-3 px-2 sm:px-3 text-xs sm:text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
            :class="activeTab === 'users' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
          >
            <UsersIcon class="w-4 h-4 inline mr-1 sm:mr-2" />
            Utilisateurs
          </button>
          <button
            v-if="authStore.isSuperAdmin"
            @click="activeTab = 'custom-roles'"
            class="py-3 px-2 sm:px-3 text-xs sm:text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
            :class="activeTab === 'custom-roles' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
          >
            <WrenchScrewdriverIcon class="w-4 h-4 inline mr-1 sm:mr-2" />
            Rôles personnalisés
          </button>
          <button
            @click="activeTab = 'system-roles'"
            class="py-3 px-2 sm:px-3 text-xs sm:text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
            :class="activeTab === 'system-roles' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
          >
            <ShieldCheckIcon class="w-4 h-4 inline mr-1 sm:mr-2" />
            Rôles système
          </button>
          <button
            @click="activeTab = 'permissions'"
            class="py-3 px-2 sm:px-3 text-xs sm:text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
            :class="activeTab === 'permissions' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
          >
            <KeyIcon class="w-4 h-4 inline mr-1 sm:mr-2" />
            Permissions
          </button>
        </nav>
      </div>

      <!-- ============================================ -->
      <!-- ACCOUNTS TAB (SuperAdmin only) -->
      <!-- ============================================ -->
      <div v-if="activeTab === 'accounts' && authStore.isSuperAdmin">
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <p class="text-sm text-muted-foreground">
            Gérez les comptes (entreprises/organisations) de la plateforme
          </p>
          <button
            @click="openAccountModal()"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-xs sm:text-sm font-medium transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-9 sm:h-10 px-3 sm:px-4 py-2"
          >
            <PlusIcon class="w-4 h-4" />
            <span>Nouveau compte</span>
          </button>
        </div>

        <div v-if="loadingAccounts" class="flex items-center justify-center py-8">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary"></div>
        </div>

        <div v-else-if="accounts.length === 0" class="flex flex-col items-center justify-center py-12 rounded-lg border bg-card">
          <BuildingOfficeIcon class="w-12 h-12 text-muted-foreground mb-4" />
          <p class="text-lg font-medium">Aucun compte</p>
          <p class="text-sm text-muted-foreground mt-1">Créez votre premier compte</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          <div
            v-for="account in accounts"
            :key="account.id"
            class="rounded-lg border bg-card shadow-sm hover:shadow-md transition-shadow"
          >
            <div class="p-4 sm:p-5">
              <div class="flex items-start justify-between gap-2 mb-3">
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-base truncate">{{ account.name }}</h3>
                  <p class="text-xs text-muted-foreground mt-0.5 truncate">{{ account.email }}</p>
                </div>
                <span
                  class="text-xs px-2 py-0.5 rounded-full font-medium flex-shrink-0"
                  :class="{
                    'bg-green-100 text-green-700': account.status === 'active',
                    'bg-red-100 text-red-700': account.status === 'suspended',
                    'bg-yellow-100 text-yellow-700': account.status === 'pending'
                  }"
                >
                  {{ statusLabels[account.status] }}
                </span>
              </div>

              <div class="grid grid-cols-2 gap-3 text-sm mb-4">
                <div>
                  <p class="text-xs text-muted-foreground">Crédits SMS</p>
                  <p class="font-semibold text-primary">{{ formatNumber(account.sms_credits) }}</p>
                </div>
                <div>
                  <p class="text-xs text-muted-foreground">Utilisateurs</p>
                  <p class="font-semibold">{{ account.users_count || 0 }}</p>
                </div>
                <div>
                  <p class="text-xs text-muted-foreground">SMS envoyés</p>
                  <p class="font-semibold">{{ formatNumber(account.sms_sent_total) }}</p>
                </div>
                <div>
                  <p class="text-xs text-muted-foreground">Budget mensuel</p>
                  <p class="font-semibold">{{ account.monthly_budget ? formatNumber(account.monthly_budget) : 'Illimité' }}</p>
                </div>
              </div>

              <div class="flex flex-wrap gap-2 pt-3 border-t">
                <button
                  @click="openAccountModal(account)"
                  class="flex-1 inline-flex items-center justify-center gap-1 text-xs font-medium rounded-md border px-2 py-1.5 hover:bg-accent"
                >
                  <PencilIcon class="w-3 h-3" />
                  Modifier
                </button>
                <button
                  @click="openCreditsModal(account)"
                  class="flex-1 inline-flex items-center justify-center gap-1 text-xs font-medium rounded-md border px-2 py-1.5 hover:bg-accent"
                >
                  <BanknotesIcon class="w-3 h-3" />
                  Crédits
                </button>
                <button
                  v-if="account.status === 'active'"
                  @click="suspendAccount(account)"
                  class="inline-flex items-center justify-center gap-1 text-xs font-medium rounded-md border border-destructive text-destructive px-2 py-1.5 hover:bg-destructive/10"
                >
                  <NoSymbolIcon class="w-3 h-3" />
                </button>
                <button
                  v-else
                  @click="activateAccount(account)"
                  class="inline-flex items-center justify-center gap-1 text-xs font-medium rounded-md border border-success text-success px-2 py-1.5 hover:bg-success/10"
                >
                  <CheckCircleIcon class="w-3 h-3" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================ -->
      <!-- USERS TAB -->
      <!-- ============================================ -->
      <div v-if="activeTab === 'users' && authStore.canManageUsers">
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div class="flex items-center gap-3">
            <p class="text-sm text-muted-foreground">
              {{ authStore.isSuperAdmin ? 'Gérez tous les utilisateurs' : 'Gérez les utilisateurs de votre compte' }}
            </p>
            <!-- Account filter for SuperAdmin -->
            <select
              v-if="authStore.isSuperAdmin && accounts.length > 0"
              v-model="selectedAccountId"
              @change="loadUsers"
              class="text-sm rounded-md border border-input bg-background px-2 py-1"
            >
              <option value="">Tous les comptes</option>
              <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
            </select>
          </div>
          <button
            v-if="authStore.canCreateAgents"
            @click="openUserModal()"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-xs sm:text-sm font-medium transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-9 sm:h-10 px-3 sm:px-4 py-2"
          >
            <PlusIcon class="w-4 h-4" />
            <span>{{ authStore.isSuperAdmin ? 'Nouvel utilisateur' : 'Nouvel agent' }}</span>
          </button>
        </div>

        <div v-if="loadingUsers" class="flex items-center justify-center py-8">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary"></div>
        </div>

        <div v-else-if="users.length === 0" class="flex flex-col items-center justify-center py-12 rounded-lg border bg-card">
          <UsersIcon class="w-12 h-12 text-muted-foreground mb-4" />
          <p class="text-lg font-medium">Aucun utilisateur</p>
          <p class="text-sm text-muted-foreground mt-1">Créez votre premier utilisateur</p>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
          <div
            v-for="user in users"
            :key="user.id"
            class="rounded-lg border bg-card shadow-sm hover:shadow-md transition-shadow"
          >
            <div class="p-4 sm:p-5">
              <div class="flex items-start justify-between gap-2 mb-3">
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-base truncate">{{ user.name }}</h3>
                  <p class="text-xs text-muted-foreground mt-0.5 truncate">{{ user.email }}</p>
                  <p v-if="user.account" class="text-xs text-primary mt-0.5 truncate">{{ user.account.name }}</p>
                </div>
                <span
                  class="text-xs px-2 py-0.5 rounded-full font-medium flex-shrink-0"
                  :class="{
                    'bg-green-100 text-green-700': user.status === 'active',
                    'bg-red-100 text-red-700': user.status === 'suspended',
                    'bg-yellow-100 text-yellow-700': user.status === 'pending'
                  }"
                >
                  {{ statusLabels[user.status] }}
                </span>
              </div>

              <div class="flex items-center gap-2 mb-3">
                <span
                  class="text-xs px-2 py-0.5 rounded-md font-medium"
                  :class="{
                    'bg-purple-100 text-purple-700': user.role === 'super_admin',
                    'bg-blue-100 text-blue-700': user.role === 'admin',
                    'bg-teal-100 text-teal-700': user.role === 'agent'
                  }"
                >
                  {{ roleLabels[user.role] }}
                </span>
              </div>

              <div class="flex flex-wrap gap-2 pt-3 border-t">
                <button
                  @click="openUserModal(user)"
                  class="flex-1 inline-flex items-center justify-center gap-1 text-xs font-medium rounded-md border px-2 py-1.5 hover:bg-accent"
                >
                  <PencilIcon class="w-3 h-3" />
                  Modifier
                </button>
                <button
                  v-if="user.status === 'active'"
                  @click="suspendUser(user)"
                  class="inline-flex items-center justify-center gap-1 text-xs font-medium rounded-md border border-destructive text-destructive px-2 py-1.5 hover:bg-destructive/10"
                >
                  <NoSymbolIcon class="w-3 h-3" />
                </button>
                <button
                  v-else
                  @click="activateUser(user)"
                  class="inline-flex items-center justify-center gap-1 text-xs font-medium rounded-md border border-success text-success px-2 py-1.5 hover:bg-success/10"
                >
                  <CheckCircleIcon class="w-3 h-3" />
                </button>
                <button
                  @click="deleteUser(user)"
                  class="inline-flex items-center justify-center gap-1 text-xs font-medium rounded-md border border-destructive text-destructive px-2 py-1.5 hover:bg-destructive/10"
                >
                  <TrashIcon class="w-3 h-3" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================ -->
      <!-- CUSTOM ROLES TAB (SuperAdmin only) -->
      <!-- ============================================ -->
      <div v-if="activeTab === 'custom-roles' && authStore.isSuperAdmin">
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <p class="text-sm text-muted-foreground">
            Créez et gérez des rôles personnalisés avec des permissions spécifiques
          </p>
          <button
            @click="openCustomRoleModal()"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-xs sm:text-sm font-medium transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-9 sm:h-10 px-3 sm:px-4 py-2"
          >
            <PlusIcon class="w-4 h-4" />
            <span>Nouveau rôle</span>
          </button>
        </div>

        <div v-if="loadingCustomRoles" class="flex items-center justify-center py-8">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary"></div>
        </div>

        <div v-else-if="customRoles.length === 0" class="flex flex-col items-center justify-center py-12 rounded-lg border bg-card">
          <WrenchScrewdriverIcon class="w-12 h-12 text-muted-foreground mb-4" />
          <p class="text-lg font-medium">Aucun rôle personnalisé</p>
          <p class="text-sm text-muted-foreground mt-1">Créez votre premier rôle personnalisé</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          <div
            v-for="role in customRoles"
            :key="role.id"
            class="rounded-lg border bg-card shadow-sm"
          >
            <div class="p-4 sm:p-5">
              <div class="flex items-start justify-between gap-2 mb-2">
                <h3 class="font-semibold text-base">{{ role.name }}</h3>
                <span v-if="role.is_system" class="text-xs px-2 py-0.5 rounded-full bg-muted">Système</span>
              </div>
              <p class="text-xs text-muted-foreground mb-3">{{ role.description || 'Aucune description' }}</p>

              <div class="flex flex-wrap gap-1 mb-3">
                <span
                  v-for="perm in (role.permissions || []).slice(0, 4)"
                  :key="perm"
                  class="text-xs px-1.5 py-0.5 rounded bg-primary/10 text-primary"
                >
                  {{ perm }}
                </span>
                <span
                  v-if="(role.permissions || []).length > 4"
                  class="text-xs px-1.5 py-0.5 rounded bg-muted"
                >
                  +{{ role.permissions.length - 4 }}
                </span>
              </div>

              <div class="flex flex-wrap gap-2 pt-3 border-t">
                <button
                  v-if="!role.is_system"
                  @click="openCustomRoleModal(role)"
                  class="flex-1 inline-flex items-center justify-center gap-1 text-xs font-medium rounded-md border px-2 py-1.5 hover:bg-accent"
                >
                  <PencilIcon class="w-3 h-3" />
                  Modifier
                </button>
                <button
                  @click="duplicateCustomRole(role)"
                  class="flex-1 inline-flex items-center justify-center gap-1 text-xs font-medium rounded-md border px-2 py-1.5 hover:bg-accent"
                >
                  <DocumentDuplicateIcon class="w-3 h-3" />
                  Dupliquer
                </button>
                <button
                  v-if="!role.is_system && (role.users_count || 0) === 0"
                  @click="deleteCustomRole(role)"
                  class="inline-flex items-center justify-center gap-1 text-xs font-medium rounded-md border border-destructive text-destructive px-2 py-1.5 hover:bg-destructive/10"
                >
                  <TrashIcon class="w-3 h-3" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================ -->
      <!-- SYSTEM ROLES TAB (read-only) -->
      <!-- ============================================ -->
      <div v-if="activeTab === 'system-roles'">
        <div class="mb-4">
          <div class="flex items-center gap-2 mb-2">
            <InformationCircleIcon class="w-5 h-5 text-muted-foreground" />
            <p class="text-sm text-muted-foreground">
              Les rôles système sont prédéfinis et ne peuvent pas être modifiés
            </p>
          </div>
        </div>

        <div v-if="loadingSystemRoles" class="flex items-center justify-center py-8">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary"></div>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div
            v-for="role in systemRoles"
            :key="role.value"
            class="rounded-lg border bg-card shadow-sm"
          >
            <div class="p-4 sm:p-5">
              <div class="flex items-start justify-between gap-2 mb-2">
                <h3 class="font-semibold text-base">{{ role.label }}</h3>
                <span class="text-xs px-2 py-0.5 rounded-full bg-muted">Niveau {{ role.level }}</span>
              </div>
              <p class="text-xs text-muted-foreground mb-3">{{ role.description }}</p>

              <div class="mb-2">
                <p class="text-xs font-medium mb-1">Permissions par défaut :</p>
                <div class="flex flex-wrap gap-1 max-h-24 overflow-y-auto">
                  <span
                    v-for="perm in role.default_permissions"
                    :key="perm"
                    class="text-xs px-1.5 py-0.5 rounded bg-primary/10 text-primary"
                  >
                    {{ perm }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================ -->
      <!-- PERMISSIONS TAB (read-only) -->
      <!-- ============================================ -->
      <div v-if="activeTab === 'permissions'">
        <div class="mb-4">
          <div class="flex items-center gap-2 mb-2">
            <InformationCircleIcon class="w-5 h-5 text-muted-foreground" />
            <p class="text-sm text-muted-foreground">
              Liste de toutes les permissions disponibles dans le système
            </p>
          </div>
        </div>

        <div v-if="loadingPermissions" class="flex items-center justify-center py-8">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary"></div>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="(permissions, category) in systemPermissions"
            :key="category"
            class="rounded-lg border bg-card shadow-sm"
          >
            <div class="p-4 sm:p-5">
              <h3 class="font-semibold text-base mb-3 capitalize">{{ category }}</h3>
              <div class="space-y-2">
                <div
                  v-for="perm in permissions"
                  :key="perm.value"
                  class="flex items-start gap-2 p-2 rounded-md bg-muted/50"
                >
                  <KeyIcon class="w-4 h-4 text-primary flex-shrink-0 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium">{{ perm.label }}</p>
                    <p class="text-xs text-muted-foreground">{{ perm.value }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================ -->
      <!-- ACCOUNT MODAL -->
      <!-- ============================================ -->
      <div v-if="showAccountModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="closeAccountModal"></div>
        <div class="relative bg-background rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <div class="sticky top-0 bg-background border-b p-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold">
              {{ editingAccount ? 'Modifier le compte' : 'Nouveau compte' }}
            </h2>
            <button @click="closeAccountModal" class="hover:bg-accent rounded-full p-1">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="saveAccount" class="p-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="text-sm font-medium">Nom du compte *</label>
                <input
                  v-model="accountForm.name"
                  type="text"
                  required
                  class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  placeholder="Nom de l'entreprise"
                />
              </div>
              <div>
                <label class="text-sm font-medium">Email *</label>
                <input
                  v-model="accountForm.email"
                  type="email"
                  required
                  class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  placeholder="contact@entreprise.com"
                />
              </div>
              <div>
                <label class="text-sm font-medium">Téléphone</label>
                <input
                  v-model="accountForm.phone"
                  type="text"
                  class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  placeholder="+229 XX XX XX XX"
                />
              </div>
              <div>
                <label class="text-sm font-medium">N° Entreprise (SIRET, etc.)</label>
                <input
                  v-model="accountForm.company_id"
                  type="text"
                  class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label class="text-sm font-medium">Crédits SMS initiaux</label>
                <input
                  v-model.number="accountForm.sms_credits"
                  type="number"
                  min="0"
                  class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label class="text-sm font-medium">Budget mensuel</label>
                <input
                  v-model.number="accountForm.monthly_budget"
                  type="number"
                  min="0"
                  class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  placeholder="Laisser vide pour illimité"
                />
              </div>
            </div>

            <!-- Admin user (only for new accounts) -->
            <div v-if="!editingAccount" class="border-t pt-4 mt-4">
              <h3 class="text-sm font-semibold mb-3">Administrateur du compte</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="text-sm font-medium">Nom *</label>
                  <input
                    v-model="accountForm.admin_name"
                    type="text"
                    required
                    class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  />
                </div>
                <div>
                  <label class="text-sm font-medium">Email *</label>
                  <input
                    v-model="accountForm.admin_email"
                    type="email"
                    required
                    class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  />
                </div>
                <div class="md:col-span-2">
                  <label class="text-sm font-medium">Mot de passe *</label>
                  <input
                    v-model="accountForm.admin_password"
                    type="password"
                    required
                    minlength="8"
                    class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  />
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
              <button
                type="button"
                @click="closeAccountModal"
                class="inline-flex items-center justify-center rounded-md text-sm font-medium border border-input bg-background hover:bg-accent h-10 px-4"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="savingAccount"
                class="inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 disabled:opacity-50"
              >
                <div v-if="savingAccount" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                {{ editingAccount ? 'Mettre à jour' : 'Créer le compte' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ============================================ -->
      <!-- CREDITS MODAL -->
      <!-- ============================================ -->
      <div v-if="showCreditsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="showCreditsModal = false"></div>
        <div class="relative bg-background rounded-lg shadow-lg w-full max-w-md">
          <div class="p-4 border-b flex items-center justify-between">
            <h2 class="text-lg font-semibold">Ajouter des crédits</h2>
            <button @click="showCreditsModal = false" class="hover:bg-accent rounded-full p-1">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="addCredits" class="p-4 space-y-4">
            <div>
              <label class="text-sm font-medium">Compte</label>
              <p class="text-sm text-muted-foreground">{{ creditsForm.accountName }}</p>
              <p class="text-sm">Solde actuel: <span class="font-semibold text-primary">{{ formatNumber(creditsForm.currentCredits) }}</span></p>
            </div>
            <div>
              <label class="text-sm font-medium">Montant à ajouter *</label>
              <input
                v-model.number="creditsForm.amount"
                type="number"
                min="1"
                required
                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label class="text-sm font-medium">Note (optionnel)</label>
              <textarea
                v-model="creditsForm.note"
                rows="2"
                class="mt-1 flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm resize-none"
              ></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
              <button
                type="button"
                @click="showCreditsModal = false"
                class="inline-flex items-center justify-center rounded-md text-sm font-medium border border-input bg-background hover:bg-accent h-10 px-4"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="savingCredits"
                class="inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium bg-success text-success-foreground hover:bg-success/90 h-10 px-4 disabled:opacity-50"
              >
                <div v-if="savingCredits" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                Ajouter
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ============================================ -->
      <!-- USER MODAL -->
      <!-- ============================================ -->
      <div v-if="showUserModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="closeUserModal"></div>
        <div class="relative bg-background rounded-lg shadow-lg w-full max-w-lg max-h-[90vh] overflow-y-auto">
          <div class="sticky top-0 bg-background border-b p-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold">
              {{ editingUser ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' }}
            </h2>
            <button @click="closeUserModal" class="hover:bg-accent rounded-full p-1">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="saveUser" class="p-4 space-y-4">
            <div>
              <label class="text-sm font-medium">Nom *</label>
              <input
                v-model="userForm.name"
                type="text"
                required
                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label class="text-sm font-medium">Email *</label>
              <input
                v-model="userForm.email"
                type="email"
                required
                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              />
            </div>
            <div v-if="!editingUser">
              <label class="text-sm font-medium">Mot de passe *</label>
              <input
                v-model="userForm.password"
                type="password"
                required
                minlength="8"
                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              />
            </div>
            <div v-if="authStore.isSuperAdmin">
              <label class="text-sm font-medium">Compte</label>
              <select
                v-model="userForm.account_id"
                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              >
                <option value="">Aucun compte</option>
                <option v-for="acc in accounts" :key="acc.id" :value="acc.id">{{ acc.name }}</option>
              </select>
            </div>
            <div>
              <label class="text-sm font-medium">Rôle *</label>
              <select
                v-model="userForm.role"
                required
                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              >
                <option v-for="role in availableRoles" :key="role.value" :value="role.value">
                  {{ role.label }}
                </option>
              </select>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
              <button
                type="button"
                @click="closeUserModal"
                class="inline-flex items-center justify-center rounded-md text-sm font-medium border border-input bg-background hover:bg-accent h-10 px-4"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="savingUser"
                class="inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 disabled:opacity-50"
              >
                <div v-if="savingUser" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                {{ editingUser ? 'Mettre à jour' : 'Créer' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ============================================ -->
      <!-- CUSTOM ROLE MODAL -->
      <!-- ============================================ -->
      <div v-if="showCustomRoleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" @click="closeCustomRoleModal"></div>
        <div class="relative bg-background rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
          <div class="sticky top-0 bg-background border-b p-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold">
              {{ editingCustomRole ? 'Modifier le rôle' : 'Nouveau rôle personnalisé' }}
            </h2>
            <button @click="closeCustomRoleModal" class="hover:bg-accent rounded-full p-1">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="saveCustomRole" class="p-4 space-y-4">
            <div>
              <label class="text-sm font-medium">Nom du rôle *</label>
              <input
                v-model="customRoleForm.name"
                type="text"
                required
                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label class="text-sm font-medium">Description</label>
              <textarea
                v-model="customRoleForm.description"
                rows="2"
                class="mt-1 flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm resize-none"
              ></textarea>
            </div>

            <div>
              <label class="text-sm font-medium mb-2 block">Permissions</label>
              <div class="border rounded-lg p-3 max-h-64 overflow-y-auto">
                <div v-for="(perms, category) in systemPermissions" :key="category" class="mb-4 last:mb-0">
                  <p class="text-xs font-semibold uppercase text-muted-foreground mb-2">{{ category }}</p>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <label
                      v-for="perm in perms"
                      :key="perm.value"
                      class="flex items-center gap-2 p-2 rounded hover:bg-accent cursor-pointer"
                    >
                      <input
                        type="checkbox"
                        :value="perm.value"
                        v-model="customRoleForm.permissions"
                        class="rounded"
                      />
                      <span class="text-sm">{{ perm.label }}</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
              <button
                type="button"
                @click="closeCustomRoleModal"
                class="inline-flex items-center justify-center rounded-md text-sm font-medium border border-input bg-background hover:bg-accent h-10 px-4"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="savingCustomRole"
                class="inline-flex items-center justify-center gap-2 rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 disabled:opacity-50"
              >
                <div v-if="savingCustomRole" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                {{ editingCustomRole ? 'Mettre à jour' : 'Créer' }}
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import MainLayout from '@/components/MainLayout.vue'
import {
  UsersIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
  XMarkIcon,
  ShieldCheckIcon,
  KeyIcon,
  InformationCircleIcon,
  CheckCircleIcon,
  NoSymbolIcon,
  BanknotesIcon,
  BuildingOfficeIcon,
  WrenchScrewdriverIcon,
  DocumentDuplicateIcon
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { showSuccess, showError, showConfirm } from '@/utils/notifications'
import api from '@/services/api'

const authStore = useAuthStore()
const route = useRoute()

// Active tab - default based on user role or query param
const getDefaultTab = () => {
  // Check query param first
  const tabParam = route.query.tab as string
  if (tabParam && ['accounts', 'users', 'custom-roles', 'system-roles', 'permissions'].includes(tabParam)) {
    return tabParam
  }
  // Default based on role
  if (authStore.isSuperAdmin) return 'accounts'
  if (authStore.canManageUsers) return 'users'
  return 'system-roles'
}
const activeTab = ref(getDefaultTab())

// Watch for route query changes
watch(() => route.query.tab, (newTab) => {
  if (newTab && ['accounts', 'users', 'custom-roles', 'system-roles', 'permissions'].includes(newTab as string)) {
    activeTab.value = newTab as string
  }
})

// Watch for auth changes to update default tab
watch(() => authStore.isSuperAdmin, (isSuperAdmin) => {
  if (isSuperAdmin && activeTab.value !== 'users' && !route.query.tab) {
    activeTab.value = 'accounts'
  }
}, { immediate: true })

// Loading states
const loadingAccounts = ref(false)
const loadingUsers = ref(false)
const loadingCustomRoles = ref(false)
const loadingSystemRoles = ref(false)
const loadingPermissions = ref(false)
const savingAccount = ref(false)
const savingUser = ref(false)
const savingCustomRole = ref(false)
const savingCredits = ref(false)

// Data
const accounts = ref<any[]>([])
const users = ref<any[]>([])
const customRoles = ref<any[]>([])
const systemRoles = ref<any[]>([])
const systemPermissions = ref<Record<string, any[]>>({})
const availableRoles = ref<any[]>([])

// Filters
const selectedAccountId = ref('')

// Modals
const showAccountModal = ref(false)
const showUserModal = ref(false)
const showCustomRoleModal = ref(false)
const showCreditsModal = ref(false)

// Editing states
const editingAccount = ref<any>(null)
const editingUser = ref<any>(null)
const editingCustomRole = ref<any>(null)

// Forms
const accountForm = ref({
  name: '',
  email: '',
  phone: '',
  company_id: '',
  sms_credits: 0,
  monthly_budget: null as number | null,
  admin_name: '',
  admin_email: '',
  admin_password: ''
})

const userForm = ref({
  name: '',
  email: '',
  password: '',
  role: 'agent',
  account_id: '' as string | number
})

const customRoleForm = ref({
  name: '',
  description: '',
  permissions: [] as string[]
})

const creditsForm = ref({
  accountId: 0,
  accountName: '',
  currentCredits: 0,
  amount: 0,
  note: ''
})

// Labels
const statusLabels: Record<string, string> = {
  active: 'Actif',
  suspended: 'Suspendu',
  pending: 'En attente'
}

const roleLabels: Record<string, string> = {
  super_admin: 'Super Admin',
  admin: 'Admin',
  agent: 'Agent'
}

// Helpers
function formatNumber(num: number): string {
  return new Intl.NumberFormat('fr-FR').format(num)
}

// Load functions
async function loadAccounts() {
  if (!authStore.isSuperAdmin) return

  loadingAccounts.value = true
  try {
    const response = await api.get('/accounts')
    accounts.value = response.data.data || []
  } catch (err: any) {
    showError('Erreur lors du chargement des comptes')
  } finally {
    loadingAccounts.value = false
  }
}

async function loadUsers() {
  loadingUsers.value = true
  try {
    const params: any = {}
    if (selectedAccountId.value) {
      params.account_id = selectedAccountId.value
    }
    const response = await api.get('/users', { params })
    // Handle paginated data - the API returns { success, data: { data: [], ... } }
    const data = response.data.data
    users.value = Array.isArray(data) ? data : (data?.data || [])
  } catch (err: any) {
    showError('Erreur lors du chargement des utilisateurs')
  } finally {
    loadingUsers.value = false
  }
}

async function loadCustomRoles() {
  if (!authStore.isSuperAdmin) return

  loadingCustomRoles.value = true
  try {
    const response = await api.get('/custom-roles')
    customRoles.value = response.data.data || []
  } catch (err: any) {
    showError('Erreur lors du chargement des rôles personnalisés')
  } finally {
    loadingCustomRoles.value = false
  }
}

async function loadSystemRoles() {
  loadingSystemRoles.value = true
  try {
    const response = await api.get('/system-roles')
    systemRoles.value = response.data.data || []
  } catch (err: any) {
    showError('Erreur lors du chargement des rôles système')
  } finally {
    loadingSystemRoles.value = false
  }
}

async function loadSystemPermissions() {
  loadingPermissions.value = true
  try {
    const response = await api.get('/system-permissions')
    systemPermissions.value = response.data.data || {}
  } catch (err: any) {
    showError('Erreur lors du chargement des permissions')
  } finally {
    loadingPermissions.value = false
  }
}

async function loadAvailableRoles() {
  try {
    const response = await api.get('/users/available-roles')
    availableRoles.value = response.data.data || []
  } catch (err: any) {
    console.error('Error loading roles:', err)
  }
}

// Account functions
function openAccountModal(account?: any) {
  editingAccount.value = account || null
  if (account) {
    accountForm.value = {
      name: account.name,
      email: account.email,
      phone: account.phone || '',
      company_id: account.company_id || '',
      sms_credits: account.sms_credits || 0,
      monthly_budget: account.monthly_budget,
      admin_name: '',
      admin_email: '',
      admin_password: ''
    }
  } else {
    accountForm.value = {
      name: '',
      email: '',
      phone: '',
      company_id: '',
      sms_credits: 0,
      monthly_budget: null,
      admin_name: '',
      admin_email: '',
      admin_password: ''
    }
  }
  showAccountModal.value = true
}

function closeAccountModal() {
  showAccountModal.value = false
  editingAccount.value = null
}

async function saveAccount() {
  savingAccount.value = true
  try {
    if (editingAccount.value) {
      await api.put(`/accounts/${editingAccount.value.id}`, accountForm.value)
      showSuccess('Compte mis à jour avec succès')
    } else {
      await api.post('/accounts', accountForm.value)
      showSuccess('Compte créé avec succès')
    }
    closeAccountModal()
    await loadAccounts()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de la sauvegarde')
  } finally {
    savingAccount.value = false
  }
}

function openCreditsModal(account: any) {
  creditsForm.value = {
    accountId: account.id,
    accountName: account.name,
    currentCredits: account.sms_credits,
    amount: 0,
    note: ''
  }
  showCreditsModal.value = true
}

async function addCredits() {
  savingCredits.value = true
  try {
    await api.post(`/accounts/${creditsForm.value.accountId}/credits`, {
      amount: creditsForm.value.amount,
      note: creditsForm.value.note
    })
    showSuccess(`${creditsForm.value.amount} crédits ajoutés avec succès`)
    showCreditsModal.value = false
    await loadAccounts()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de l\'ajout des crédits')
  } finally {
    savingCredits.value = false
  }
}

async function suspendAccount(account: any) {
  const confirmed = await showConfirm('Suspendre ce compte ?', 'Tous les utilisateurs de ce compte seront également suspendus.')
  if (!confirmed) return

  try {
    await api.post(`/accounts/${account.id}/suspend`)
    showSuccess('Compte suspendu')
    await loadAccounts()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de la suspension')
  }
}

async function activateAccount(account: any) {
  try {
    await api.post(`/accounts/${account.id}/activate`)
    showSuccess('Compte activé')
    await loadAccounts()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de l\'activation')
  }
}

// User functions
function openUserModal(user?: any) {
  editingUser.value = user || null
  if (user) {
    userForm.value = {
      name: user.name,
      email: user.email,
      password: '',
      role: user.role,
      account_id: user.account_id || ''
    }
  } else {
    userForm.value = {
      name: '',
      email: '',
      password: '',
      role: 'agent',
      account_id: authStore.user?.account_id || ''
    }
  }
  showUserModal.value = true
}

function closeUserModal() {
  showUserModal.value = false
  editingUser.value = null
}

async function saveUser() {
  savingUser.value = true
  try {
    const data: any = { ...userForm.value }
    if (!data.password) delete data.password
    if (!data.account_id) delete data.account_id

    if (editingUser.value) {
      await api.put(`/users/${editingUser.value.id}`, data)
      showSuccess('Utilisateur mis à jour')
    } else {
      await api.post('/users', data)
      showSuccess('Utilisateur créé')
    }
    closeUserModal()
    await loadUsers()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de la sauvegarde')
  } finally {
    savingUser.value = false
  }
}

async function suspendUser(user: any) {
  const confirmed = await showConfirm('Suspendre cet utilisateur ?')
  if (!confirmed) return

  try {
    await api.post(`/users/${user.id}/suspend`)
    showSuccess('Utilisateur suspendu')
    await loadUsers()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de la suspension')
  }
}

async function activateUser(user: any) {
  try {
    await api.post(`/users/${user.id}/activate`)
    showSuccess('Utilisateur activé')
    await loadUsers()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de l\'activation')
  }
}

async function deleteUser(user: any) {
  const confirmed = await showConfirm('Supprimer cet utilisateur ?', 'Cette action est irréversible.')
  if (!confirmed) return

  try {
    await api.delete(`/users/${user.id}`)
    showSuccess('Utilisateur supprimé')
    await loadUsers()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de la suppression')
  }
}

// Custom role functions
function openCustomRoleModal(role?: any) {
  editingCustomRole.value = role || null
  if (role) {
    customRoleForm.value = {
      name: role.name,
      description: role.description || '',
      permissions: [...(role.permissions || [])]
    }
  } else {
    customRoleForm.value = {
      name: '',
      description: '',
      permissions: []
    }
  }
  showCustomRoleModal.value = true
}

function closeCustomRoleModal() {
  showCustomRoleModal.value = false
  editingCustomRole.value = null
}

async function saveCustomRole() {
  savingCustomRole.value = true
  try {
    if (editingCustomRole.value) {
      await api.put(`/custom-roles/${editingCustomRole.value.id}`, customRoleForm.value)
      showSuccess('Rôle mis à jour')
    } else {
      await api.post('/custom-roles', customRoleForm.value)
      showSuccess('Rôle créé')
    }
    closeCustomRoleModal()
    await loadCustomRoles()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de la sauvegarde')
  } finally {
    savingCustomRole.value = false
  }
}

async function duplicateCustomRole(role: any) {
  try {
    await api.post(`/custom-roles/${role.id}/duplicate`)
    showSuccess('Rôle dupliqué')
    await loadCustomRoles()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de la duplication')
  }
}

async function deleteCustomRole(role: any) {
  const confirmed = await showConfirm('Supprimer ce rôle ?')
  if (!confirmed) return

  try {
    await api.delete(`/custom-roles/${role.id}`)
    showSuccess('Rôle supprimé')
    await loadCustomRoles()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de la suppression')
  }
}

// Initialize
onMounted(async () => {
  await Promise.all([
    loadAccounts(),
    loadUsers(),
    loadCustomRoles(),
    loadSystemRoles(),
    loadSystemPermissions(),
    loadAvailableRoles()
  ])
})
</script>
