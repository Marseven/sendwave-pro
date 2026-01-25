<template>
  <MainLayout>
    <div class="p-4 sm:p-6 lg:p-8">
      <!-- Header -->
      <div class="mb-4 sm:mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
          <div>
            <h1 class="text-xl sm:text-2xl font-bold flex items-center gap-2">
              <ChartBarIcon class="w-6 h-6 sm:w-7 sm:h-7 text-primary" />
              Rapports & Statistiques
            </h1>
            <p class="text-sm text-muted-foreground mt-1">Analysez les performances de vos envois</p>
          </div>
          <div class="flex items-center gap-2">
            <button
              @click="exportReport('pdf')"
              :disabled="exporting"
              class="inline-flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 border rounded-lg hover:bg-accent text-xs sm:text-sm font-medium disabled:opacity-50"
            >
              <ArrowDownTrayIcon class="w-4 h-4" />
              PDF
            </button>
            <button
              @click="exportReport('excel')"
              :disabled="exporting"
              class="inline-flex items-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 border rounded-lg hover:bg-accent text-xs sm:text-sm font-medium disabled:opacity-50"
            >
              <ArrowDownTrayIcon class="w-4 h-4" />
              Excel
            </button>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0 mb-4 sm:mb-6">
        <TabNav
          v-model="activeTab"
          :tabs="tabs"
        />
      </div>

      <!-- Date Range Filter (shared) -->
      <div class="rounded-lg border bg-card p-3 sm:p-4 mb-4 sm:mb-6">
        <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-end gap-2 sm:gap-4">
          <div class="col-span-1 sm:flex-1 sm:min-w-[150px]">
            <label class="text-xs sm:text-sm font-medium mb-1 block">Date début</label>
            <input
              v-model="startDate"
              type="date"
              class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-2 sm:px-3 py-2 text-xs sm:text-sm"
            />
          </div>
          <div class="col-span-1 sm:flex-1 sm:min-w-[150px]">
            <label class="text-xs sm:text-sm font-medium mb-1 block">Date fin</label>
            <input
              v-model="endDate"
              type="date"
              class="flex h-9 sm:h-10 w-full rounded-md border border-input bg-background px-2 sm:px-3 py-2 text-xs sm:text-sm"
            />
          </div>
          <button
            @click="loadData"
            class="h-9 sm:h-10 px-3 sm:px-4 bg-primary text-primary-foreground rounded-lg text-xs sm:text-sm font-medium hover:bg-primary/90"
          >
            Appliquer
          </button>
          <select
            v-model="quickPeriod"
            @change="applyQuickPeriod"
            class="h-9 sm:h-10 px-2 sm:px-3 rounded-md border border-input bg-background text-xs sm:text-sm"
          >
            <option value="">Période...</option>
            <option value="today">Aujourd'hui</option>
            <option value="yesterday">Hier</option>
            <option value="week">Cette semaine</option>
            <option value="month">Ce mois</option>
            <option value="quarter">Ce trimestre</option>
          </select>
        </div>
      </div>

      <!-- Campaign Report Tab -->
      <div v-if="activeTab === 'campaign'">
        <div v-if="loading" class="flex items-center justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
        </div>
        <div v-else class="space-y-4 sm:space-y-6">
          <!-- Overview Cards -->
          <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-4">
            <div class="rounded-lg border bg-card p-3 sm:p-5">
              <div class="flex items-center gap-1.5 sm:gap-2 mb-2 sm:mb-3">
                <RocketLaunchIcon class="w-4 h-4 sm:w-5 sm:h-5 text-primary" />
                <span class="text-xs sm:text-sm font-medium text-muted-foreground">Campagnes</span>
              </div>
              <p class="text-lg sm:text-2xl font-bold">{{ dashboard?.overview.campaigns_executed || 0 }}</p>
              <p class="text-xs text-muted-foreground mt-1 hidden sm:block">sur la période</p>
            </div>
            <div class="rounded-lg border bg-card p-3 sm:p-5">
              <div class="flex items-center gap-1.5 sm:gap-2 mb-2 sm:mb-3">
                <PaperAirplaneIcon class="w-4 h-4 sm:w-5 sm:h-5 text-primary" />
                <span class="text-xs sm:text-sm font-medium text-muted-foreground">SMS Envoyés</span>
              </div>
              <p class="text-lg sm:text-2xl font-bold">{{ formatNumber(dashboard?.overview.sms_sent || 0) }}</p>
              <p class="text-xs text-success mt-1 hidden sm:block">{{ dashboard?.overview.success_rate?.toFixed(1) }}% livrés</p>
            </div>
            <div class="rounded-lg border bg-card p-3 sm:p-5">
              <div class="flex items-center gap-1.5 sm:gap-2 mb-2 sm:mb-3">
                <CheckCircleIcon class="w-4 h-4 sm:w-5 sm:h-5 text-success" />
                <span class="text-xs sm:text-sm font-medium text-muted-foreground">Taux succès</span>
              </div>
              <p class="text-lg sm:text-2xl font-bold text-success">{{ dashboard?.overview.success_rate?.toFixed(1) || 0 }}%</p>
              <p class="text-xs text-muted-foreground mt-1 hidden sm:block">{{ dashboard?.overview.sms_delivered || 0 }} livrés</p>
            </div>
            <div class="rounded-lg border bg-card p-3 sm:p-5">
              <div class="flex items-center gap-1.5 sm:gap-2 mb-2 sm:mb-3">
                <BanknotesIcon class="w-4 h-4 sm:w-5 sm:h-5 text-warning" />
                <span class="text-xs sm:text-sm font-medium text-muted-foreground">Coût Total</span>
              </div>
              <p class="text-lg sm:text-2xl font-bold">{{ (dashboard?.overview.total_cost || 0).toLocaleString() }}</p>
              <p class="text-xs text-muted-foreground mt-1 hidden sm:block">{{ dashboard?.overview.average_cost_per_sms?.toFixed(0) || 0 }} XAF/SMS</p>
            </div>
          </div>

          <!-- Campaigns Table -->
          <div class="rounded-lg border bg-card">
            <div class="p-3 sm:p-4 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-2">
              <h3 class="font-semibold text-sm sm:text-base">Liste des campagnes</h3>
              <input
                v-model="campaignSearch"
                type="text"
                placeholder="Rechercher..."
                class="h-8 sm:h-9 px-3 rounded-md border border-input bg-background text-xs sm:text-sm w-full sm:w-64"
              />
            </div>
            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
              <table class="w-full">
                <thead class="bg-muted/50">
                  <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium">Campagne</th>
                    <th class="px-4 py-3 text-left text-sm font-medium">Date</th>
                    <th class="px-4 py-3 text-center text-sm font-medium">Envoyés</th>
                    <th class="px-4 py-3 text-center text-sm font-medium">Livrés</th>
                    <th class="px-4 py-3 text-center text-sm font-medium">Échecs</th>
                    <th class="px-4 py-3 text-center text-sm font-medium">Taux</th>
                    <th class="px-4 py-3 text-right text-sm font-medium">Coût</th>
                    <th class="px-4 py-3 text-center text-sm font-medium">Statut</th>
                  </tr>
                </thead>
                <tbody class="divide-y">
                  <tr v-for="campaign in filteredCampaigns" :key="campaign.id" class="hover:bg-muted/30">
                    <td class="px-4 py-3">
                      <div class="font-medium">{{ campaign.name }}</div>
                      <div class="text-xs text-muted-foreground">ID: {{ campaign.id }}</div>
                    </td>
                    <td class="px-4 py-3 text-sm">{{ formatDate(campaign.sent_at || campaign.created_at) }}</td>
                    <td class="px-4 py-3 text-center">{{ campaign.messages_sent?.toLocaleString() }}</td>
                    <td class="px-4 py-3 text-center text-success">{{ campaign.delivered?.toLocaleString() || '-' }}</td>
                    <td class="px-4 py-3 text-center text-destructive">{{ campaign.failed?.toLocaleString() || '-' }}</td>
                    <td class="px-4 py-3 text-center">
                      <span :class="getSuccessRateClass(campaign.success_rate)">
                        {{ campaign.success_rate?.toFixed(1) || 0 }}%
                      </span>
                    </td>
                    <td class="px-4 py-3 text-right">{{ (campaign.cost || 0).toLocaleString() }} XAF</td>
                    <td class="px-4 py-3 text-center">
                      <span class="px-2 py-1 text-xs rounded-full" :class="getStatusClass(campaign.status)">
                        {{ campaign.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <!-- Mobile Cards -->
            <div class="md:hidden divide-y">
              <div v-for="campaign in filteredCampaigns" :key="campaign.id" class="p-3">
                <div class="flex items-start justify-between mb-2">
                  <div>
                    <div class="font-medium text-sm">{{ campaign.name }}</div>
                    <div class="text-xs text-muted-foreground">{{ formatDate(campaign.sent_at || campaign.created_at) }}</div>
                  </div>
                  <span class="px-2 py-0.5 text-xs rounded-full" :class="getStatusClass(campaign.status)">
                    {{ campaign.status }}
                  </span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                  <div><span class="text-muted-foreground">Envoyés:</span> {{ campaign.messages_sent?.toLocaleString() }}</div>
                  <div><span class="text-muted-foreground">Taux:</span> <span :class="getSuccessRateClass(campaign.success_rate)">{{ campaign.success_rate?.toFixed(1) || 0 }}%</span></div>
                  <div><span class="text-muted-foreground">Livrés:</span> <span class="text-success">{{ campaign.delivered?.toLocaleString() || '-' }}</span></div>
                  <div><span class="text-muted-foreground">Coût:</span> {{ (campaign.cost || 0).toLocaleString() }} XAF</div>
                </div>
              </div>
            </div>
            <div v-if="filteredCampaigns.length === 0" class="p-6 sm:p-8 text-center text-muted-foreground text-sm">
              Aucune campagne sur cette période
            </div>
          </div>
        </div>
      </div>

      <!-- Delivery Report Tab -->
      <div v-if="activeTab === 'delivery'" class="space-y-4 sm:space-y-6">
        <!-- Status Distribution -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-4">
          <div class="rounded-lg border bg-card p-3 sm:p-5">
            <div class="flex items-center gap-1.5 sm:gap-2 mb-2">
              <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-success"></div>
              <span class="text-xs sm:text-sm font-medium">Livrés</span>
            </div>
            <p class="text-lg sm:text-2xl font-bold text-success">{{ deliveryStats.delivered }}</p>
          </div>
          <div class="rounded-lg border bg-card p-3 sm:p-5">
            <div class="flex items-center gap-1.5 sm:gap-2 mb-2">
              <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-warning"></div>
              <span class="text-xs sm:text-sm font-medium">En attente</span>
            </div>
            <p class="text-lg sm:text-2xl font-bold text-warning">{{ deliveryStats.pending }}</p>
          </div>
          <div class="rounded-lg border bg-card p-3 sm:p-5">
            <div class="flex items-center gap-1.5 sm:gap-2 mb-2">
              <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-destructive"></div>
              <span class="text-xs sm:text-sm font-medium">Échecs</span>
            </div>
            <p class="text-lg sm:text-2xl font-bold text-destructive">{{ deliveryStats.failed }}</p>
          </div>
          <div class="rounded-lg border bg-card p-3 sm:p-5">
            <div class="flex items-center gap-1.5 sm:gap-2 mb-2">
              <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-primary"></div>
              <span class="text-xs sm:text-sm font-medium">Taux global</span>
            </div>
            <p class="text-lg sm:text-2xl font-bold">{{ deliveryStats.rate.toFixed(1) }}%</p>
          </div>
        </div>

        <!-- Messages Table -->
        <div class="rounded-lg border bg-card">
          <div class="p-3 sm:p-4 border-b flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <h3 class="font-semibold text-sm sm:text-base">Historique des livraisons</h3>
            <select v-model="deliveryFilter" class="h-8 sm:h-9 px-2 sm:px-3 rounded-md border border-input bg-background text-xs sm:text-sm">
              <option value="">Tous les statuts</option>
              <option value="delivered">Livrés</option>
              <option value="sent">Envoyés</option>
              <option value="failed">Échecs</option>
            </select>
          </div>
          <!-- Desktop Table -->
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full">
              <thead class="bg-muted/50">
                <tr>
                  <th class="px-4 py-3 text-left text-sm font-medium">Destinataire</th>
                  <th class="px-4 py-3 text-left text-sm font-medium">Message</th>
                  <th class="px-4 py-3 text-left text-sm font-medium">Opérateur</th>
                  <th class="px-4 py-3 text-left text-sm font-medium">Date envoi</th>
                  <th class="px-4 py-3 text-center text-sm font-medium">Statut</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="msg in deliveryMessages" :key="msg.id" class="hover:bg-muted/30">
                  <td class="px-4 py-3">
                    <div class="font-medium">{{ msg.recipient_phone }}</div>
                    <div class="text-xs text-muted-foreground">{{ msg.recipient_name || '-' }}</div>
                  </td>
                  <td class="px-4 py-3 text-sm max-w-xs truncate">{{ msg.content }}</td>
                  <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs rounded" :class="msg.provider === 'airtel' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'">
                      {{ msg.provider }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm">{{ formatDateTime(msg.sent_at) }}</td>
                  <td class="px-4 py-3 text-center">
                    <span class="px-2 py-1 text-xs rounded-full" :class="getDeliveryStatusClass(msg.status)">
                      {{ msg.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Mobile Cards -->
          <div class="md:hidden divide-y">
            <div v-for="msg in deliveryMessages" :key="msg.id" class="p-3">
              <div class="flex items-start justify-between mb-2">
                <div>
                  <div class="font-medium text-sm">{{ msg.recipient_phone }}</div>
                  <div class="text-xs text-muted-foreground">{{ msg.recipient_name || '-' }}</div>
                </div>
                <span class="px-2 py-0.5 text-xs rounded-full" :class="getDeliveryStatusClass(msg.status)">
                  {{ msg.status }}
                </span>
              </div>
              <p class="text-xs text-muted-foreground mb-2 line-clamp-2">{{ msg.content }}</p>
              <div class="flex items-center justify-between text-xs">
                <span class="px-2 py-0.5 rounded" :class="msg.provider === 'airtel' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'">
                  {{ msg.provider }}
                </span>
                <span class="text-muted-foreground">{{ formatDateTime(msg.sent_at) }}</span>
              </div>
            </div>
          </div>
          <div v-if="deliveryMessages.length === 0" class="p-6 sm:p-8 text-center text-muted-foreground text-sm">
            Aucun message sur cette période
          </div>
        </div>
      </div>

      <!-- Schedule Report Tab -->
      <div v-if="activeTab === 'schedule'" class="space-y-4 sm:space-y-6">
        <div class="grid grid-cols-3 gap-2 sm:gap-4">
          <div class="rounded-lg border bg-card p-3 sm:p-5">
            <div class="flex items-center gap-1.5 sm:gap-2 mb-2">
              <CalendarIcon class="w-4 h-4 sm:w-5 sm:h-5 text-primary" />
              <span class="text-xs sm:text-sm font-medium">Planifiées</span>
            </div>
            <p class="text-lg sm:text-2xl font-bold">{{ scheduledCampaigns.filter(c => c.status === 'scheduled').length }}</p>
          </div>
          <div class="rounded-lg border bg-card p-3 sm:p-5">
            <div class="flex items-center gap-1.5 sm:gap-2 mb-2">
              <ClockIcon class="w-4 h-4 sm:w-5 sm:h-5 text-warning" />
              <span class="text-xs sm:text-sm font-medium">En attente</span>
            </div>
            <p class="text-lg sm:text-2xl font-bold">{{ scheduledCampaigns.filter(c => c.status === 'pending').length }}</p>
          </div>
          <div class="rounded-lg border bg-card p-3 sm:p-5">
            <div class="flex items-center gap-1.5 sm:gap-2 mb-2">
              <CheckCircleIcon class="w-4 h-4 sm:w-5 sm:h-5 text-success" />
              <span class="text-xs sm:text-sm font-medium">Exécutées</span>
            </div>
            <p class="text-lg sm:text-2xl font-bold">{{ scheduledCampaigns.filter(c => c.status === 'completed').length }}</p>
          </div>
        </div>

        <!-- Scheduled Campaigns List -->
        <div class="rounded-lg border bg-card">
          <div class="p-3 sm:p-4 border-b">
            <h3 class="font-semibold text-sm sm:text-base">Campagnes planifiées</h3>
          </div>
          <div class="divide-y">
            <div v-for="campaign in scheduledCampaigns" :key="campaign.id" class="p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4 hover:bg-muted/30">
              <div class="flex items-center gap-3 sm:gap-4">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                  <CalendarIcon class="w-4 h-4 sm:w-5 sm:h-5 text-primary" />
                </div>
                <div>
                  <p class="font-medium text-sm sm:text-base">{{ campaign.name }}</p>
                  <p class="text-xs sm:text-sm text-muted-foreground">{{ campaign.recipients_count }} destinataires</p>
                </div>
              </div>
              <div class="text-left sm:text-right pl-11 sm:pl-0">
                <p class="font-medium text-xs sm:text-base">{{ formatDateTime(campaign.scheduled_at) }}</p>
                <span class="px-2 py-0.5 sm:py-1 text-xs rounded-full" :class="getStatusClass(campaign.status)">
                  {{ campaign.status }}
                </span>
              </div>
            </div>
          </div>
          <div v-if="scheduledCampaigns.length === 0" class="p-6 sm:p-8 text-center text-muted-foreground text-sm">
            Aucune campagne planifiée
          </div>
        </div>
      </div>

      <!-- Archived Report Tab -->
      <div v-if="activeTab === 'archived'" class="space-y-4 sm:space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
          <div
            v-for="archive in archivedPeriods"
            :key="archive.period"
            class="rounded-lg border bg-card p-4 sm:p-5 hover:shadow-md transition-shadow cursor-pointer"
            @click="loadArchive(archive.period)"
          >
            <div class="flex items-center justify-between mb-3 sm:mb-4">
              <h3 class="font-semibold text-sm sm:text-base">{{ archive.label }}</h3>
              <ArchiveBoxIcon class="w-4 h-4 sm:w-5 sm:h-5 text-muted-foreground" />
            </div>
            <div class="grid grid-cols-2 gap-3 sm:gap-4">
              <div>
                <p class="text-xs text-muted-foreground">SMS</p>
                <p class="text-base sm:text-lg font-bold">{{ archive.total_sms.toLocaleString() }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Cout</p>
                <p class="text-base sm:text-lg font-bold">{{ archive.total_cost.toLocaleString() }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Campagnes</p>
                <p class="text-base sm:text-lg font-bold">{{ archive.campaigns_count }}</p>
              </div>
              <div>
                <p class="text-xs text-muted-foreground">Taux</p>
                <p class="text-base sm:text-lg font-bold text-success">{{ archive.success_rate.toFixed(1) }}%</p>
              </div>
            </div>
            <div class="mt-3 sm:mt-4 pt-3 sm:pt-4 border-t flex justify-end">
              <button class="text-xs text-primary hover:underline flex items-center gap-1">
                <ArrowDownTrayIcon class="w-4 h-4" />
                Exporter
              </button>
            </div>
          </div>
        </div>
        <div v-if="archivedPeriods.length === 0" class="text-center py-8 sm:py-12">
          <ArchiveBoxIcon class="w-12 h-12 sm:w-16 sm:h-16 mx-auto text-muted-foreground mb-4" />
          <h3 class="text-base sm:text-lg font-semibold mb-2">Aucune archive</h3>
          <p class="text-sm text-muted-foreground">Les archives seront créées automatiquement chaque mois</p>
        </div>
      </div>

      <!-- Credit History Tab -->
      <div v-if="activeTab === 'credit'" class="space-y-4 sm:space-y-6">
        <!-- Credit Balance -->
        <div class="grid grid-cols-3 gap-2 sm:gap-4">
          <div class="rounded-lg border bg-card p-3 sm:p-5">
            <div class="flex items-center gap-1.5 sm:gap-2 mb-2">
              <BanknotesIcon class="w-4 h-4 sm:w-5 sm:h-5 text-success" />
              <span class="text-xs sm:text-sm font-medium">Achetés</span>
            </div>
            <p class="text-sm sm:text-2xl font-bold text-success">{{ creditStats.purchased.toLocaleString() }}</p>
          </div>
          <div class="rounded-lg border bg-card p-3 sm:p-5">
            <div class="flex items-center gap-1.5 sm:gap-2 mb-2">
              <ArrowTrendingDownIcon class="w-4 h-4 sm:w-5 sm:h-5 text-destructive" />
              <span class="text-xs sm:text-sm font-medium">Utilisés</span>
            </div>
            <p class="text-sm sm:text-2xl font-bold text-destructive">{{ creditStats.used.toLocaleString() }}</p>
          </div>
          <div class="rounded-lg border bg-card p-3 sm:p-5">
            <div class="flex items-center gap-1.5 sm:gap-2 mb-2">
              <WalletIcon class="w-4 h-4 sm:w-5 sm:h-5 text-primary" />
              <span class="text-xs sm:text-sm font-medium">Solde</span>
            </div>
            <p class="text-sm sm:text-2xl font-bold text-primary">{{ creditStats.balance.toLocaleString() }}</p>
          </div>
        </div>

        <!-- Credit Transactions -->
        <div class="rounded-lg border bg-card">
          <div class="p-3 sm:p-4 border-b">
            <h3 class="font-semibold text-sm sm:text-base">Historique des credits</h3>
          </div>
          <div class="divide-y">
            <div v-for="tx in creditTransactions" :key="tx.id" class="p-3 sm:p-4 flex items-center justify-between gap-3 hover:bg-muted/30">
              <div class="flex items-center gap-2 sm:gap-4">
                <div
                  class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center flex-shrink-0"
                  :class="tx.type === 'credit' ? 'bg-success/10' : 'bg-destructive/10'"
                >
                  <ArrowUpIcon v-if="tx.type === 'credit'" class="w-4 h-4 sm:w-5 sm:h-5 text-success" />
                  <ArrowDownIcon v-else class="w-4 h-4 sm:w-5 sm:h-5 text-destructive" />
                </div>
                <div class="min-w-0">
                  <p class="font-medium text-sm sm:text-base truncate">{{ tx.description }}</p>
                  <p class="text-xs sm:text-sm text-muted-foreground">{{ formatDateTime(tx.created_at) }}</p>
                </div>
              </div>
              <div class="text-right flex-shrink-0">
                <p class="font-bold text-sm sm:text-base" :class="tx.type === 'credit' ? 'text-success' : 'text-destructive'">
                  {{ tx.type === 'credit' ? '+' : '-' }}{{ tx.amount.toLocaleString() }}
                </p>
                <p class="text-xs text-muted-foreground hidden sm:block">Solde: {{ tx.balance_after.toLocaleString() }} XAF</p>
              </div>
            </div>
          </div>
          <div v-if="creditTransactions.length === 0" class="p-6 sm:p-8 text-center text-muted-foreground text-sm">
            Aucune transaction
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import MainLayout from '@/components/MainLayout.vue'
import TabNav from '@/components/ui/TabNav.vue'
import {
  ChartBarIcon,
  PaperAirplaneIcon,
  CheckCircleIcon,
  RocketLaunchIcon,
  ClockIcon,
  CalendarIcon,
  ArrowDownTrayIcon,
  BanknotesIcon,
  ArrowTrendingDownIcon,
  ArchiveBoxIcon,
  ArrowUpIcon,
  ArrowDownIcon
} from '@heroicons/vue/24/outline'
import { WalletIcon } from '@heroicons/vue/24/solid'
import analyticsService, { type AnalyticsDashboard } from '@/services/analyticsService'
import apiClient from '@/services/api'
import { showSuccess, showError } from '@/utils/notifications'

// Tabs
const tabs = [
  { id: 'campaign', label: 'Rapport campagnes' },
  { id: 'delivery', label: 'Rapport livraisons' },
  { id: 'schedule', label: 'Rapport planifiés' },
  { id: 'archived', label: 'Archives' },
  { id: 'credit', label: 'Historique crédits' }
]
const activeTab = ref('campaign')

// Date filters
const startDate = ref('')
const endDate = ref('')
const quickPeriod = ref('')

// Loading states
const loading = ref(true)
const exporting = ref(false)

// Campaign Report data
const dashboard = ref<AnalyticsDashboard | null>(null)
const campaigns = ref<any[]>([])
const campaignSearch = ref('')

// Delivery Report data
const deliveryFilter = ref('')
const deliveryMessages = ref<any[]>([])
const deliveryStats = ref({ delivered: 0, pending: 0, failed: 0, rate: 0 })

// Schedule Report data
const scheduledCampaigns = ref<any[]>([])

// Archived Report data
const archivedPeriods = ref<any[]>([])

// Credit History data
const creditStats = ref({ purchased: 0, used: 0, balance: 0 })
const creditTransactions = ref<any[]>([])

// Computed
const filteredCampaigns = computed(() => {
  if (!campaignSearch.value) return campaigns.value
  const search = campaignSearch.value.toLowerCase()
  return campaigns.value.filter(c => c.name.toLowerCase().includes(search))
})

// Methods
function setDefaultDateRange() {
  const end = new Date()
  const start = new Date()
  start.setDate(start.getDate() - 30)
  endDate.value = end.toISOString().split('T')[0]
  startDate.value = start.toISOString().split('T')[0]
}

function applyQuickPeriod() {
  const now = new Date()
  let start = new Date()

  switch (quickPeriod.value) {
    case 'today':
      start = new Date()
      break
    case 'yesterday':
      start = new Date()
      start.setDate(start.getDate() - 1)
      break
    case 'week':
      start.setDate(now.getDate() - 7)
      break
    case 'month':
      start.setMonth(now.getMonth() - 1)
      break
    case 'quarter':
      start.setMonth(now.getMonth() - 3)
      break
  }

  startDate.value = start.toISOString().split('T')[0]
  endDate.value = now.toISOString().split('T')[0]
  loadData()
}

async function loadData() {
  loading.value = true
  try {
    await Promise.all([
      loadDashboard(),
      loadDeliveryReport(),
      loadScheduledCampaigns(),
      loadArchivedPeriods(),
      loadCreditHistory()
    ])
  } finally {
    loading.value = false
  }
}

async function loadDashboard() {
  try {
    const response = await analyticsService.getDashboard('month')
    dashboard.value = response.data

    // Also load campaigns
    const campaignsRes = await apiClient.get('/campaigns/history', {
      params: { start_date: startDate.value, end_date: endDate.value }
    })
    campaigns.value = campaignsRes.data.data || campaignsRes.data || []
  } catch (error) {
    console.error('Error loading dashboard:', error)
  }
}

async function loadDeliveryReport() {
  try {
    const response = await apiClient.get('/messages/history', {
      params: {
        start_date: startDate.value,
        end_date: endDate.value,
        per_page: 100
      }
    })
    deliveryMessages.value = response.data.data || response.data || []

    // Calculate stats
    const delivered = deliveryMessages.value.filter(m => m.status === 'delivered' || m.status === 'sent').length
    const failed = deliveryMessages.value.filter(m => m.status === 'failed').length
    const pending = deliveryMessages.value.filter(m => m.status === 'pending').length
    const total = deliveryMessages.value.length

    deliveryStats.value = {
      delivered,
      pending,
      failed,
      rate: total > 0 ? (delivered / total) * 100 : 0
    }
  } catch (error) {
    console.error('Error loading delivery report:', error)
  }
}

async function loadScheduledCampaigns() {
  try {
    const response = await apiClient.get('/campaigns', {
      params: { status: 'scheduled,pending' }
    })
    scheduledCampaigns.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Error loading scheduled campaigns:', error)
  }
}

async function loadArchivedPeriods() {
  try {
    const response = await apiClient.get('/sms-analytics/periods')
    archivedPeriods.value = (response.data.periods || []).map((p: any) => ({
      period: p.period_key,
      label: p.formatted,
      total_sms: p.total_sms,
      total_cost: p.total_cost,
      campaigns_count: p.campaigns_count || 0,
      success_rate: p.success_rate || 0
    }))
  } catch (error) {
    console.error('Error loading archived periods:', error)
    archivedPeriods.value = []
  }
}

async function loadCreditHistory() {
  // Placeholder - would load from credits API
  creditStats.value = {
    purchased: 500000,
    used: 125000,
    balance: 375000
  }
  creditTransactions.value = [
    { id: 1, type: 'credit', description: 'Achat de credits', amount: 100000, balance_after: 475000, created_at: new Date().toISOString() },
    { id: 2, type: 'debit', description: 'Campagne "Promotion Noel"', amount: 25000, balance_after: 450000, created_at: new Date(Date.now() - 86400000).toISOString() },
    { id: 3, type: 'debit', description: 'Envois transactionnels', amount: 12500, balance_after: 437500, created_at: new Date(Date.now() - 172800000).toISOString() }
  ]
}

async function loadArchive(period: string) {
  // Would load detailed archive data
  console.log('Loading archive for period:', period)
}

async function exportReport(format: 'pdf' | 'excel') {
  try {
    exporting.value = true
    let blob: Blob
    let filename: string

    if (format === 'pdf') {
      blob = await analyticsService.exportPdf(startDate.value, endDate.value)
      filename = `rapport_${startDate.value}_${endDate.value}.pdf`
    } else {
      blob = await analyticsService.exportExcel(startDate.value, endDate.value)
      filename = `rapport_${startDate.value}_${endDate.value}.xlsx`
    }

    analyticsService.downloadFile(blob, filename)
    showSuccess(`Rapport ${format.toUpperCase()} téléchargé`)
  } catch (error: any) {
    showError(error.response?.data?.message || 'Erreur lors de l\'export')
  } finally {
    exporting.value = false
  }
}

function formatNumber(num: number): string {
  if (num >= 1000000) return `${(num / 1000000).toFixed(1)}M`
  if (num >= 1000) return `${(num / 1000).toFixed(1)}K`
  return num.toString()
}

function formatDate(date: string): string {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

function formatDateTime(date: string): string {
  if (!date) return '-'
  return new Date(date).toLocaleString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function getStatusClass(status: string): string {
  const s = (status || '').toLowerCase()
  if (['completed', 'termine', 'done'].includes(s)) return 'bg-success/10 text-success'
  if (['active', 'sending', 'running'].includes(s)) return 'bg-blue-100 text-blue-700'
  if (['scheduled', 'pending', 'draft'].includes(s)) return 'bg-warning/10 text-warning'
  if (['failed', 'error'].includes(s)) return 'bg-destructive/10 text-destructive'
  return 'bg-muted text-muted-foreground'
}

function getDeliveryStatusClass(status: string): string {
  const s = (status || '').toLowerCase()
  if (['delivered', 'sent'].includes(s)) return 'bg-success/10 text-success'
  if (['pending'].includes(s)) return 'bg-warning/10 text-warning'
  if (['failed'].includes(s)) return 'bg-destructive/10 text-destructive'
  return 'bg-muted text-muted-foreground'
}

function getSuccessRateClass(rate: number): string {
  if (rate >= 90) return 'text-success font-semibold'
  if (rate >= 70) return 'text-warning font-semibold'
  return 'text-destructive font-semibold'
}

onMounted(() => {
  setDefaultDateRange()
  loadData()
})
</script>
