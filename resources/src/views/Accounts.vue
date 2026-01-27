<template>
  <MainLayout>
    <div class="p-4 sm:p-6 lg:p-8">
      <!-- Header -->
      <div class="mb-4 sm:mb-6 lg:mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold">Gestion des comptes</h1>
        <p class="text-sm text-muted-foreground mt-1 sm:mt-2">
          Gérez les utilisateurs, sous-comptes et rôles personnalisés
        </p>
      </div>

      <!-- Tabs -->
      <div class="mb-6 border-b">
        <nav class="flex gap-4 -mb-px">
          <button
            v-if="authStore.canManageUsers"
            @click="activeTab = 'users'"
            class="py-3 px-1 text-sm font-medium border-b-2 transition-colors"
            :class="activeTab === 'users' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
          >
            <UsersIcon class="w-4 h-4 inline mr-2" />
            Utilisateurs
          </button>
          <button
            @click="activeTab = 'subaccounts'"
            class="py-3 px-1 text-sm font-medium border-b-2 transition-colors"
            :class="activeTab === 'subaccounts' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
          >
            <UserGroupIcon class="w-4 h-4 inline mr-2" />
            Sous-comptes
          </button>
          <button
            v-if="authStore.isSuperAdmin"
            @click="activeTab = 'roles'"
            class="py-3 px-1 text-sm font-medium border-b-2 transition-colors"
            :class="activeTab === 'roles' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground'"
          >
            <ShieldCheckIcon class="w-4 h-4 inline mr-2" />
            Rôles personnalisés
          </button>
        </nav>
      </div>

      <!-- Users Tab -->
      <div v-if="activeTab === 'users' && authStore.canManageUsers">
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <p class="text-sm text-muted-foreground">
            {{ authStore.isSuperAdmin ? 'Gérez tous les utilisateurs de la plateforme' : 'Gérez vos agents' }}
          </p>
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
          <p class="text-sm text-muted-foreground mt-1">Créez votre premier agent</p>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
          <div
            v-for="user in users"
            :key="user.id"
            class="rounded-lg border bg-card shadow-sm hover:shadow-md transition-shadow"
          >
            <div class="p-4 sm:p-6">
              <div class="flex items-start justify-between gap-2 mb-3">
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-base truncate">{{ user.name }}</h3>
                  <p class="text-xs text-muted-foreground mt-0.5 truncate">{{ user.email }}</p>
                </div>
                <span
                  class="text-xs px-2 py-0.5 rounded-full font-medium flex-shrink-0"
                  :class="{
                    'bg-green-100 text-green-700': user.status === 'active',
                    'bg-red-100 text-red-700': user.status === 'suspended',
                    'bg-yellow-100 text-yellow-700': user.status === 'pending'
                  }"
                >
                  {{ userStatusLabels[user.status] }}
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
                  {{ userRoleLabels[user.role] }}
                </span>
                <span v-if="user.custom_role_name" class="text-xs text-muted-foreground">
                  ({{ user.custom_role_name }})
                </span>
              </div>

              <div class="text-xs text-muted-foreground">
                Créé le {{ formatDate(user.created_at) }}
              </div>
            </div>

            <div class="border-t p-3 bg-muted/30 flex gap-2">
              <button
                @click="openUserModal(user)"
                class="flex-1 inline-flex items-center justify-center gap-1 whitespace-nowrap rounded-md text-xs font-medium transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8"
              >
                <PencilIcon class="w-3 h-3" />
                <span>Modifier</span>
              </button>
              <button
                @click="openUserPermissionsModal(user)"
                class="inline-flex items-center justify-center rounded-md text-xs font-medium transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 px-2"
              >
                <KeyIcon class="w-4 h-4" />
              </button>
              <button
                v-if="user.status === 'active'"
                @click="suspendUser(user)"
                class="inline-flex items-center justify-center rounded-md text-xs font-medium transition-colors hover:bg-destructive/10 hover:text-destructive h-8 w-8"
              >
                <LockClosedIcon class="w-4 h-4" />
              </button>
              <button
                v-else
                @click="activateUser(user)"
                class="inline-flex items-center justify-center rounded-md text-xs font-medium transition-colors hover:bg-green-100 hover:text-green-700 h-8 w-8"
              >
                <LockOpenIcon class="w-4 h-4" />
              </button>
              <button
                @click="deleteUser(user)"
                class="inline-flex items-center justify-center rounded-md text-xs font-medium transition-colors hover:bg-destructive/10 hover:text-destructive h-8 w-8"
              >
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- SubAccounts Tab (existing functionality) -->
      <div v-if="activeTab === 'subaccounts'">
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <p class="text-sm text-muted-foreground">Accès cloisonnés avec permissions et crédits</p>
          <button
            @click="openSubAccountModal()"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-xs sm:text-sm font-medium transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-9 sm:h-10 px-3 sm:px-4 py-2"
          >
            <PlusIcon class="w-4 h-4" />
            <span>Ajouter un sous-compte</span>
          </button>
        </div>

        <div v-if="loadingSubAccounts" class="flex items-center justify-center py-8">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary"></div>
        </div>

        <div v-else-if="subAccounts.length === 0" class="flex flex-col items-center justify-center py-12 rounded-lg border bg-card">
          <UserGroupIcon class="w-12 h-12 text-muted-foreground mb-4" />
          <p class="text-lg font-medium">Aucun sous-compte</p>
          <p class="text-sm text-muted-foreground mt-1">Créez des comptes avec accès cloisonné</p>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
          <div
            v-for="account in subAccounts"
            :key="account.id"
            class="rounded-lg border bg-card shadow-sm hover:shadow-md transition-shadow"
          >
            <div class="p-4 sm:p-6">
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
                    'bg-gray-100 text-gray-700': account.status === 'inactive'
                  }"
                >
                  {{ subAccountStatusLabels[account.status] }}
                </span>
              </div>

              <div class="flex items-center gap-2 mb-3">
                <span
                  class="text-xs px-2 py-0.5 rounded-md font-medium"
                  :class="{
                    'bg-purple-100 text-purple-700': account.role === 'admin',
                    'bg-blue-100 text-blue-700': account.role === 'manager',
                    'bg-teal-100 text-teal-700': account.role === 'sender',
                    'bg-gray-100 text-gray-700': account.role === 'viewer'
                  }"
                >
                  {{ subAccountRoleLabels[account.role] }}
                </span>
              </div>

              <div class="space-y-2 mb-3">
                <div class="flex justify-between items-center text-xs">
                  <span class="text-muted-foreground">Crédits SMS</span>
                  <span class="font-semibold">
                    {{ account.remaining_credits === null ? 'Illimité' : account.remaining_credits.toLocaleString('fr-FR') }}
                  </span>
                </div>
                <div class="flex justify-between items-center text-xs">
                  <span class="text-muted-foreground">Utilisés</span>
                  <span>{{ (account.sms_used || 0).toLocaleString('fr-FR') }}</span>
                </div>
                <div v-if="account.remaining_credits !== null" class="w-full bg-gray-200 rounded-full h-1.5">
                  <div
                    class="bg-primary h-full rounded-full transition-all"
                    :style="{ width: getSubAccountUsagePercentage(account) + '%' }"
                  ></div>
                </div>
              </div>
            </div>

            <div class="border-t p-3 bg-muted/30 flex gap-2">
              <button
                @click="openSubAccountModal(account)"
                class="flex-1 inline-flex items-center justify-center gap-1 whitespace-nowrap rounded-md text-xs font-medium transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8"
              >
                <PencilIcon class="w-3 h-3" />
                <span>Modifier</span>
              </button>
              <button
                @click="openSubAccountPermissionsModal(account)"
                class="inline-flex items-center justify-center rounded-md text-xs font-medium transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 px-2"
              >
                <KeyIcon class="w-4 h-4" />
              </button>
              <button
                v-if="account.status === 'active'"
                @click="suspendSubAccount(account)"
                class="inline-flex items-center justify-center rounded-md text-xs font-medium transition-colors hover:bg-destructive/10 hover:text-destructive h-8 w-8"
              >
                <LockClosedIcon class="w-4 h-4" />
              </button>
              <button
                v-else
                @click="activateSubAccount(account)"
                class="inline-flex items-center justify-center rounded-md text-xs font-medium transition-colors hover:bg-green-100 hover:text-green-700 h-8 w-8"
              >
                <LockOpenIcon class="w-4 h-4" />
              </button>
              <button
                @click="deleteSubAccount(account)"
                class="inline-flex items-center justify-center rounded-md text-xs font-medium transition-colors hover:bg-destructive/10 hover:text-destructive h-8 w-8"
              >
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Custom Roles Tab (SuperAdmin only) -->
      <div v-if="activeTab === 'roles' && authStore.isSuperAdmin">
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <p class="text-sm text-muted-foreground">Créez des rôles avec des permissions sur mesure</p>
          <button
            @click="openRoleModal()"
            class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-xs sm:text-sm font-medium transition-colors bg-primary text-primary-foreground hover:bg-primary/90 h-9 sm:h-10 px-3 sm:px-4 py-2"
          >
            <PlusIcon class="w-4 h-4" />
            <span>Nouveau rôle</span>
          </button>
        </div>

        <div v-if="loadingRoles" class="flex items-center justify-center py-8">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-primary"></div>
        </div>

        <div v-else-if="customRoles.length === 0" class="flex flex-col items-center justify-center py-12 rounded-lg border bg-card">
          <ShieldCheckIcon class="w-12 h-12 text-muted-foreground mb-4" />
          <p class="text-lg font-medium">Aucun rôle personnalisé</p>
          <p class="text-sm text-muted-foreground mt-1">Créez des rôles avec des permissions spécifiques</p>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
          <div
            v-for="role in customRoles"
            :key="role.id"
            class="rounded-lg border bg-card shadow-sm hover:shadow-md transition-shadow"
          >
            <div class="p-4 sm:p-6">
              <div class="flex items-start justify-between gap-2 mb-3">
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-base truncate">{{ role.name }}</h3>
                  <p class="text-xs text-muted-foreground mt-0.5">{{ role.slug }}</p>
                </div>
                <span
                  v-if="role.is_system"
                  class="text-xs px-2 py-0.5 rounded-full font-medium bg-gray-100 text-gray-700"
                >
                  Système
                </span>
              </div>

              <p v-if="role.description" class="text-sm text-muted-foreground mb-3">
                {{ role.description }}
              </p>

              <div class="flex flex-wrap gap-1 mb-3">
                <span
                  v-for="perm in role.permissions.slice(0, 3)"
                  :key="perm"
                  class="text-xs px-2 py-0.5 rounded bg-blue-50 text-blue-700"
                >
                  {{ perm }}
                </span>
                <span
                  v-if="role.permissions.length > 3"
                  class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-600"
                >
                  +{{ role.permissions.length - 3 }}
                </span>
              </div>

              <div class="text-xs text-muted-foreground">
                {{ role.users_count || 0 }} utilisateur(s)
              </div>
            </div>

            <div class="border-t p-3 bg-muted/30 flex gap-2">
              <button
                @click="openRoleModal(role)"
                :disabled="role.is_system"
                class="flex-1 inline-flex items-center justify-center gap-1 whitespace-nowrap rounded-md text-xs font-medium transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <PencilIcon class="w-3 h-3" />
                <span>Modifier</span>
              </button>
              <button
                @click="duplicateRole(role)"
                class="inline-flex items-center justify-center rounded-md text-xs font-medium transition-colors border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 px-2"
                title="Dupliquer"
              >
                <DocumentDuplicateIcon class="w-4 h-4" />
              </button>
              <button
                @click="deleteRole(role)"
                :disabled="role.is_system || (role.users_count && role.users_count > 0)"
                class="inline-flex items-center justify-center rounded-md text-xs font-medium transition-colors hover:bg-destructive/10 hover:text-destructive h-8 w-8 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- User Modal -->
    <div
      v-if="showUserModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeUserModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-background border-b p-6">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">{{ editingUser ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' }}</h2>
            <button @click="closeUserModal" class="hover:bg-accent rounded-full p-1">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
        </div>

        <form @submit.prevent="saveUser" class="p-6 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Nom complet *</label>
              <input
                v-model="userForm.name"
                type="text"
                required
                placeholder="Jean Dupont"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              />
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium">Email *</label>
              <input
                v-model="userForm.email"
                type="email"
                required
                :disabled="!!editingUser"
                placeholder="jean@example.com"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm disabled:opacity-50"
              />
            </div>
          </div>

          <div v-if="!editingUser" class="space-y-2">
            <label class="text-sm font-medium">Mot de passe *</label>
            <input
              v-model="userForm.password"
              type="password"
              :required="!editingUser"
              placeholder="Minimum 8 caractères"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Rôle *</label>
              <select
                v-model="userForm.role"
                required
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              >
                <option v-for="role in availableUserRoles" :key="role.value" :value="role.value">
                  {{ role.label }}
                </option>
              </select>
            </div>

            <div v-if="authStore.isSuperAdmin && customRoles.length > 0" class="space-y-2">
              <label class="text-sm font-medium">Rôle personnalisé</label>
              <select
                v-model="userForm.custom_role_id"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              >
                <option :value="null">-- Permissions par défaut --</option>
                <option v-for="role in customRoles" :key="role.id" :value="role.id">
                  {{ role.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="flex gap-3 pt-4 border-t">
            <button
              type="button"
              @click="closeUserModal"
              class="flex-1 inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="savingUser"
              class="flex-1 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
            >
              <div v-if="savingUser" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              <span>{{ savingUser ? 'Enregistrement...' : (editingUser ? 'Modifier' : 'Créer') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- SubAccount Modal -->
    <div
      v-if="showSubAccountModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeSubAccountModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-background border-b p-6">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">{{ editingSubAccount ? 'Modifier le sous-compte' : 'Nouveau sous-compte' }}</h2>
            <button @click="closeSubAccountModal" class="hover:bg-accent rounded-full p-1">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
        </div>

        <form @submit.prevent="saveSubAccount" class="p-6 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Nom complet *</label>
              <input
                v-model="subAccountForm.name"
                type="text"
                required
                placeholder="Jean Dupont"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              />
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium">Email *</label>
              <input
                v-model="subAccountForm.email"
                type="email"
                required
                :disabled="!!editingSubAccount"
                placeholder="jean@example.com"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm disabled:opacity-50"
              />
            </div>
          </div>

          <div v-if="!editingSubAccount" class="space-y-2">
            <label class="text-sm font-medium">Mot de passe *</label>
            <input
              v-model="subAccountForm.password"
              type="password"
              :required="!editingSubAccount"
              placeholder="Minimum 8 caractères"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Rôle *</label>
              <select
                v-model="subAccountForm.role"
                required
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              >
                <option value="admin">Admin (tous les droits)</option>
                <option value="manager">Manager (gestion avancée)</option>
                <option value="sender">Sender (envoi SMS)</option>
                <option value="viewer">Viewer (consultation uniquement)</option>
              </select>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium">Statut</label>
              <select
                v-model="subAccountForm.status"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              >
                <option value="active">Actif</option>
                <option value="suspended">Suspendu</option>
              </select>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">Limite de crédits SMS</label>
            <div class="flex gap-2">
              <input
                v-model.number="subAccountForm.sms_credit_limit"
                type="number"
                min="0"
                :disabled="unlimitedCredits"
                placeholder="1000"
                class="flex h-10 flex-1 rounded-md border border-input bg-background px-3 py-2 text-sm disabled:opacity-50"
              />
              <label class="inline-flex items-center gap-2 px-4 py-2 border rounded-md cursor-pointer hover:bg-accent">
                <input v-model="unlimitedCredits" type="checkbox" class="rounded border-gray-300" />
                <span class="text-sm">Illimité</span>
              </label>
            </div>
          </div>

          <div class="flex gap-3 pt-4 border-t">
            <button
              type="button"
              @click="closeSubAccountModal"
              class="flex-1 inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="savingSubAccount"
              class="flex-1 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
            >
              <div v-if="savingSubAccount" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              <span>{{ savingSubAccount ? 'Enregistrement...' : (editingSubAccount ? 'Modifier' : 'Créer') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Custom Role Modal -->
    <div
      v-if="showRoleModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeRoleModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-background border-b p-6">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">{{ editingRole ? 'Modifier le rôle' : 'Nouveau rôle' }}</h2>
            <button @click="closeRoleModal" class="hover:bg-accent rounded-full p-1">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
        </div>

        <form @submit.prevent="saveRole" class="p-6 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-sm font-medium">Nom du rôle *</label>
              <input
                v-model="roleForm.name"
                type="text"
                required
                placeholder="Manager Marketing"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              />
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium">Slug</label>
              <input
                v-model="roleForm.slug"
                type="text"
                placeholder="manager-marketing"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              />
              <p class="text-xs text-muted-foreground">Généré automatiquement si vide</p>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-medium">Description</label>
            <textarea
              v-model="roleForm.description"
              rows="2"
              placeholder="Description du rôle..."
              class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            ></textarea>
          </div>

          <div class="space-y-3">
            <label class="text-sm font-medium">Permissions *</label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div v-for="perm in allPermissions" :key="perm.value" class="flex items-start gap-2">
                <input
                  v-model="roleForm.permissions"
                  :value="perm.value"
                  type="checkbox"
                  :id="'role-perm-' + perm.value"
                  class="mt-1 rounded border-gray-300"
                />
                <label :for="'role-perm-' + perm.value" class="flex-1 cursor-pointer">
                  <div class="text-sm font-medium">{{ perm.label }}</div>
                </label>
              </div>
            </div>
          </div>

          <div class="flex gap-3 pt-4 border-t">
            <button
              type="button"
              @click="closeRoleModal"
              class="flex-1 inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
            >
              Annuler
            </button>
            <button
              type="submit"
              :disabled="savingRole || roleForm.permissions.length === 0"
              class="flex-1 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
            >
              <div v-if="savingRole" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
              <span>{{ savingRole ? 'Enregistrement...' : (editingRole ? 'Modifier' : 'Créer') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Permissions Modal (for users) -->
    <div
      v-if="showUserPermissionsModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeUserPermissionsModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-lg">
        <div class="border-b p-6">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">Permissions utilisateur</h2>
            <button @click="closeUserPermissionsModal" class="hover:bg-accent rounded-full p-1">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
          <p class="text-sm text-muted-foreground mt-1">{{ editingUser?.name }}</p>
        </div>

        <div class="p-6 space-y-4 max-h-96 overflow-y-auto">
          <div v-for="perm in availableUserPermissions" :key="perm.value" class="flex items-start gap-3">
            <input
              v-model="selectedUserPermissions"
              :value="perm.value"
              type="checkbox"
              :id="'user-perm-' + perm.value"
              class="mt-1 rounded border-gray-300"
            />
            <label :for="'user-perm-' + perm.value" class="flex-1 cursor-pointer">
              <div class="font-medium">{{ perm.label }}</div>
            </label>
          </div>
        </div>

        <div class="border-t p-6 flex gap-3">
          <button
            type="button"
            @click="closeUserPermissionsModal"
            class="flex-1 inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
          >
            Annuler
          </button>
          <button
            @click="saveUserPermissions"
            :disabled="savingUserPermissions"
            class="flex-1 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
          >
            <div v-if="savingUserPermissions" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
            <span>{{ savingUserPermissions ? 'Enregistrement...' : 'Enregistrer' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Permissions Modal (for sub-accounts) -->
    <div
      v-if="showSubAccountPermissionsModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="closeSubAccountPermissionsModal"
    >
      <div class="bg-background rounded-lg shadow-lg w-full max-w-lg">
        <div class="border-b p-6">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">Permissions sous-compte</h2>
            <button @click="closeSubAccountPermissionsModal" class="hover:bg-accent rounded-full p-1">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
          <p class="text-sm text-muted-foreground mt-1">{{ editingSubAccount?.name }}</p>
        </div>

        <div class="p-6 space-y-4 max-h-96 overflow-y-auto">
          <div v-for="perm in subAccountPermissions" :key="perm.value" class="flex items-start gap-3">
            <input
              v-model="selectedSubAccountPermissions"
              :value="perm.value"
              type="checkbox"
              :id="'sub-perm-' + perm.value"
              class="mt-1 rounded border-gray-300"
            />
            <label :for="'sub-perm-' + perm.value" class="flex-1 cursor-pointer">
              <div class="font-medium">{{ perm.label }}</div>
              <div class="text-sm text-muted-foreground">{{ perm.description }}</div>
            </label>
          </div>
        </div>

        <div class="border-t p-6 flex gap-3">
          <button
            type="button"
            @click="closeSubAccountPermissionsModal"
            class="flex-1 inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
          >
            Annuler
          </button>
          <button
            @click="saveSubAccountPermissions"
            :disabled="savingSubAccountPermissions"
            class="flex-1 inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2"
          >
            <div v-if="savingSubAccountPermissions" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
            <span>{{ savingSubAccountPermissions ? 'Enregistrement...' : 'Enregistrer' }}</span>
          </button>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import MainLayout from '@/components/MainLayout.vue'
import { useAuthStore } from '@/stores/auth'
import {
  PlusIcon, PencilIcon, TrashIcon, XMarkIcon, UsersIcon, UserGroupIcon,
  KeyIcon, LockClosedIcon, LockOpenIcon, ShieldCheckIcon, DocumentDuplicateIcon
} from '@heroicons/vue/24/outline'
import { subAccountService } from '@/services/subAccountService'
import api from '@/services/api'
import { showSuccess, showError, showConfirm } from '@/utils/notifications'

const authStore = useAuthStore()

// Tab state
const activeTab = ref(authStore.canManageUsers ? 'users' : 'subaccounts')

// Users state
interface User {
  id: number
  name: string
  email: string
  role: string
  custom_role_id?: number
  custom_role_name?: string
  permissions: string[]
  status: string
  created_at: string
}

const users = ref<User[]>([])
const loadingUsers = ref(false)
const showUserModal = ref(false)
const editingUser = ref<User | null>(null)
const savingUser = ref(false)
const showUserPermissionsModal = ref(false)
const selectedUserPermissions = ref<string[]>([])
const savingUserPermissions = ref(false)
const availableUserRoles = ref<{ value: string; label: string }[]>([])
const availableUserPermissions = ref<{ value: string; label: string }[]>([])

const userForm = ref({
  name: '',
  email: '',
  password: '',
  role: 'agent',
  custom_role_id: null as number | null
})

const userRoleLabels: Record<string, string> = {
  super_admin: 'Super Admin',
  admin: 'Administrateur',
  agent: 'Agent'
}

const userStatusLabels: Record<string, string> = {
  active: 'Actif',
  suspended: 'Suspendu',
  pending: 'En attente'
}

// SubAccounts state
interface SubAccount {
  id: number
  name: string
  email: string
  role: string
  status: string
  sms_credit_limit: number | null
  sms_used: number
  remaining_credits: number | null
  permissions: string[]
}

const subAccounts = ref<SubAccount[]>([])
const loadingSubAccounts = ref(false)
const showSubAccountModal = ref(false)
const editingSubAccount = ref<SubAccount | null>(null)
const savingSubAccount = ref(false)
const unlimitedCredits = ref(true)
const showSubAccountPermissionsModal = ref(false)
const selectedSubAccountPermissions = ref<string[]>([])
const savingSubAccountPermissions = ref(false)

const subAccountForm = ref({
  name: '',
  email: '',
  password: '',
  role: 'sender',
  status: 'active',
  sms_credit_limit: null as number | null
})

const subAccountRoleLabels: Record<string, string> = {
  admin: 'Administrateur',
  manager: 'Manager',
  sender: 'Expéditeur',
  viewer: 'Observateur'
}

const subAccountStatusLabels: Record<string, string> = {
  active: 'Actif',
  suspended: 'Suspendu',
  inactive: 'Inactif'
}

const subAccountPermissions = [
  { value: 'send_sms', label: 'Envoyer SMS', description: 'Permet d\'envoyer des SMS' },
  { value: 'view_history', label: 'Voir l\'historique', description: 'Consulter l\'historique des SMS' },
  { value: 'manage_contacts', label: 'Gérer les contacts', description: 'Ajouter, modifier et supprimer des contacts' },
  { value: 'manage_groups', label: 'Gérer les groupes', description: 'Créer et gérer des groupes de contacts' },
  { value: 'create_campaigns', label: 'Créer des campagnes', description: 'Créer et lancer des campagnes SMS' },
  { value: 'view_analytics', label: 'Voir les statistiques', description: 'Accéder aux rapports et statistiques' },
  { value: 'manage_templates', label: 'Gérer les modèles', description: 'Créer et modifier des modèles' },
  { value: 'export_data', label: 'Exporter les données', description: 'Exporter les données' }
]

// Custom Roles state
interface CustomRole {
  id: number
  name: string
  slug: string
  description?: string
  permissions: string[]
  is_system: boolean
  users_count?: number
}

const customRoles = ref<CustomRole[]>([])
const loadingRoles = ref(false)
const showRoleModal = ref(false)
const editingRole = ref<CustomRole | null>(null)
const savingRole = ref(false)

const roleForm = ref({
  name: '',
  slug: '',
  description: '',
  permissions: [] as string[]
})

const allPermissions = [
  { value: 'manage_users', label: 'Gérer les utilisateurs' },
  { value: 'manage_settings', label: 'Gérer les paramètres' },
  { value: 'manage_api_keys', label: 'Gérer les clés API' },
  { value: 'manage_webhooks', label: 'Gérer les webhooks' },
  { value: 'manage_sub_accounts', label: 'Gérer les sous-comptes' },
  { value: 'view_audit_logs', label: 'Voir les journaux d\'audit' },
  { value: 'send_sms', label: 'Envoyer SMS' },
  { value: 'view_history', label: 'Voir l\'historique' },
  { value: 'manage_contacts', label: 'Gérer les contacts' },
  { value: 'manage_groups', label: 'Gérer les groupes' },
  { value: 'create_campaigns', label: 'Créer des campagnes' },
  { value: 'view_analytics', label: 'Voir les analytics' },
  { value: 'manage_templates', label: 'Gérer les modèles' },
  { value: 'export_data', label: 'Exporter les données' }
]

// Watchers
watch(unlimitedCredits, (value) => {
  if (value) {
    subAccountForm.value.sms_credit_limit = null
  }
})

// Load functions
async function loadUsers() {
  if (!authStore.canManageUsers) return
  loadingUsers.value = true
  try {
    const response = await api.get('/users')
    users.value = response.data.data.data || response.data.data || []
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors du chargement des utilisateurs')
  } finally {
    loadingUsers.value = false
  }
}

async function loadAvailableRoles() {
  try {
    const response = await api.get('/users/available-roles')
    availableUserRoles.value = response.data.data
  } catch (err) {
    console.error('Error loading available roles:', err)
  }
}

async function loadAvailablePermissions() {
  try {
    const response = await api.get('/users/available-permissions')
    const grouped = response.data.data
    availableUserPermissions.value = Object.values(grouped).flat() as any[]
  } catch (err) {
    console.error('Error loading available permissions:', err)
  }
}

async function loadSubAccounts() {
  loadingSubAccounts.value = true
  try {
    const data = await subAccountService.getAll()
    subAccounts.value = data as any[]
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors du chargement')
  } finally {
    loadingSubAccounts.value = false
  }
}

async function loadCustomRoles() {
  if (!authStore.isSuperAdmin) return
  loadingRoles.value = true
  try {
    const response = await api.get('/custom-roles')
    customRoles.value = response.data.data || []
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors du chargement des rôles')
  } finally {
    loadingRoles.value = false
  }
}

// User functions
function openUserModal(user?: User) {
  editingUser.value = user || null
  userForm.value = user ? {
    name: user.name,
    email: user.email,
    password: '',
    role: user.role,
    custom_role_id: user.custom_role_id || null
  } : {
    name: '',
    email: '',
    password: '',
    role: 'agent',
    custom_role_id: null
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
    if (editingUser.value && !data.password) {
      delete data.password
    }

    if (editingUser.value) {
      await api.put(`/users/${editingUser.value.id}`, data)
      showSuccess('Utilisateur modifié avec succès')
    } else {
      await api.post('/users', data)
      showSuccess('Utilisateur créé avec succès')
    }
    closeUserModal()
    await loadUsers()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de l\'enregistrement')
  } finally {
    savingUser.value = false
  }
}

function openUserPermissionsModal(user: User) {
  editingUser.value = user
  selectedUserPermissions.value = [...(user.permissions || [])]
  showUserPermissionsModal.value = true
}

function closeUserPermissionsModal() {
  showUserPermissionsModal.value = false
  editingUser.value = null
}

async function saveUserPermissions() {
  if (!editingUser.value) return
  savingUserPermissions.value = true
  try {
    await api.put(`/users/${editingUser.value.id}/permissions`, {
      permissions: selectedUserPermissions.value
    })
    showSuccess('Permissions mises à jour')
    closeUserPermissionsModal()
    await loadUsers()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur lors de la mise à jour')
  } finally {
    savingUserPermissions.value = false
  }
}

async function suspendUser(user: User) {
  const confirmed = await showConfirm('Suspendre l\'utilisateur ?', `Voulez-vous suspendre "${user.name}" ?`)
  if (confirmed) {
    try {
      await api.post(`/users/${user.id}/suspend`)
      showSuccess('Utilisateur suspendu')
      await loadUsers()
    } catch (err: any) {
      showError(err.response?.data?.message || 'Erreur')
    }
  }
}

async function activateUser(user: User) {
  try {
    await api.post(`/users/${user.id}/activate`)
    showSuccess('Utilisateur activé')
    await loadUsers()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur')
  }
}

async function deleteUser(user: User) {
  const confirmed = await showConfirm('Supprimer l\'utilisateur ?', `Êtes-vous sûr de vouloir supprimer "${user.name}" ?`)
  if (confirmed) {
    try {
      await api.delete(`/users/${user.id}`)
      showSuccess('Utilisateur supprimé')
      await loadUsers()
    } catch (err: any) {
      showError(err.response?.data?.message || 'Erreur')
    }
  }
}

// SubAccount functions
function openSubAccountModal(account?: SubAccount) {
  editingSubAccount.value = account || null
  subAccountForm.value = account ? {
    name: account.name,
    email: account.email,
    password: '',
    role: account.role,
    status: account.status,
    sms_credit_limit: account.sms_credit_limit
  } : {
    name: '',
    email: '',
    password: '',
    role: 'sender',
    status: 'active',
    sms_credit_limit: null
  }
  unlimitedCredits.value = !account?.sms_credit_limit
  showSubAccountModal.value = true
}

function closeSubAccountModal() {
  showSubAccountModal.value = false
  editingSubAccount.value = null
}

async function saveSubAccount() {
  savingSubAccount.value = true
  try {
    const data: any = { ...subAccountForm.value }
    if (unlimitedCredits.value) {
      data.sms_credit_limit = null
    }
    if (editingSubAccount.value && !data.password) {
      delete data.password
    }

    if (editingSubAccount.value) {
      await subAccountService.update(editingSubAccount.value.id, data)
      showSuccess('Sous-compte modifié')
    } else {
      await subAccountService.create(data)
      showSuccess('Sous-compte créé')
    }
    closeSubAccountModal()
    await loadSubAccounts()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur')
  } finally {
    savingSubAccount.value = false
  }
}

function openSubAccountPermissionsModal(account: SubAccount) {
  editingSubAccount.value = account
  selectedSubAccountPermissions.value = [...(account.permissions || [])]
  showSubAccountPermissionsModal.value = true
}

function closeSubAccountPermissionsModal() {
  showSubAccountPermissionsModal.value = false
  editingSubAccount.value = null
}

async function saveSubAccountPermissions() {
  if (!editingSubAccount.value) return
  savingSubAccountPermissions.value = true
  try {
    await api.post(`/sub-accounts/${editingSubAccount.value.id}/permissions`, {
      permissions: selectedSubAccountPermissions.value
    })
    showSuccess('Permissions mises à jour')
    closeSubAccountPermissionsModal()
    await loadSubAccounts()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur')
  } finally {
    savingSubAccountPermissions.value = false
  }
}

async function suspendSubAccount(account: SubAccount) {
  const confirmed = await showConfirm('Suspendre le compte ?', `Voulez-vous suspendre "${account.name}" ?`)
  if (confirmed) {
    try {
      await api.post(`/sub-accounts/${account.id}/suspend`)
      showSuccess('Compte suspendu')
      await loadSubAccounts()
    } catch (err: any) {
      showError(err.response?.data?.message || 'Erreur')
    }
  }
}

async function activateSubAccount(account: SubAccount) {
  try {
    await api.post(`/sub-accounts/${account.id}/activate`)
    showSuccess('Compte activé')
    await loadSubAccounts()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur')
  }
}

async function deleteSubAccount(account: SubAccount) {
  const confirmed = await showConfirm('Supprimer le compte ?', `Êtes-vous sûr de vouloir supprimer "${account.name}" ?`)
  if (confirmed) {
    try {
      await subAccountService.delete(account.id)
      showSuccess('Compte supprimé')
      await loadSubAccounts()
    } catch (err: any) {
      showError(err.response?.data?.message || 'Erreur')
    }
  }
}

function getSubAccountUsagePercentage(account: SubAccount): number {
  if (account.sms_credit_limit === null) return 0
  return Math.min(100, (account.sms_used / account.sms_credit_limit) * 100)
}

// Custom Role functions
function openRoleModal(role?: CustomRole) {
  editingRole.value = role || null
  roleForm.value = role ? {
    name: role.name,
    slug: role.slug,
    description: role.description || '',
    permissions: [...role.permissions]
  } : {
    name: '',
    slug: '',
    description: '',
    permissions: []
  }
  showRoleModal.value = true
}

function closeRoleModal() {
  showRoleModal.value = false
  editingRole.value = null
}

async function saveRole() {
  savingRole.value = true
  try {
    if (editingRole.value) {
      await api.put(`/custom-roles/${editingRole.value.id}`, roleForm.value)
      showSuccess('Rôle modifié')
    } else {
      await api.post('/custom-roles', roleForm.value)
      showSuccess('Rôle créé')
    }
    closeRoleModal()
    await loadCustomRoles()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur')
  } finally {
    savingRole.value = false
  }
}

async function duplicateRole(role: CustomRole) {
  try {
    await api.post(`/custom-roles/${role.id}/duplicate`)
    showSuccess('Rôle dupliqué')
    await loadCustomRoles()
  } catch (err: any) {
    showError(err.response?.data?.message || 'Erreur')
  }
}

async function deleteRole(role: CustomRole) {
  const confirmed = await showConfirm('Supprimer le rôle ?', `Êtes-vous sûr de vouloir supprimer "${role.name}" ?`)
  if (confirmed) {
    try {
      await api.delete(`/custom-roles/${role.id}`)
      showSuccess('Rôle supprimé')
      await loadCustomRoles()
    } catch (err: any) {
      showError(err.response?.data?.message || 'Erreur')
    }
  }
}

// Utilities
function formatDate(dateString: string): string {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(date)
}

// On mounted
onMounted(async () => {
  await Promise.all([
    loadSubAccounts(),
    authStore.canManageUsers ? loadUsers() : Promise.resolve(),
    authStore.canManageUsers ? loadAvailableRoles() : Promise.resolve(),
    authStore.canManageUsers ? loadAvailablePermissions() : Promise.resolve(),
    authStore.isSuperAdmin ? loadCustomRoles() : Promise.resolve()
  ])
})
</script>
