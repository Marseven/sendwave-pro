<template>
  <MainLayout>
    <div class="p-8">
      <div class="mb-8">
        <div class="flex items-center gap-2">
          <PaperAirplaneIcon class="w-8 h-8 text-primary" />
          <h1 class="text-3xl font-bold">Envoyer un message</h1>
        </div>
        <p class="text-muted-foreground mt-2">Envoyez un SMS rapide à un ou plusieurs destinataires</p>
      </div>

      <div class="max-w-3xl mx-auto">
        <div class="rounded-lg border bg-card shadow-sm p-8 space-y-6">
          <!-- Type de destinataire -->
          <div class="space-y-3">
            <label class="text-sm font-medium">Type de destinataire</label>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <label class="flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-accent/50 transition-colors"
                :class="recipientType === 'contact' ? 'border-primary bg-primary/5' : 'border-border'">
                <input
                  v-model="recipientType"
                  type="radio"
                  value="contact"
                  class="mt-1 w-4 h-4"
                />
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <UserIcon class="w-5 h-5 text-primary" />
                    <div class="font-semibold text-sm">Contact</div>
                  </div>
                  <div class="text-xs text-muted-foreground mt-1">Depuis vos contacts</div>
                </div>
              </label>

              <label class="flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-accent/50 transition-colors"
                :class="recipientType === 'phone' ? 'border-primary bg-primary/5' : 'border-border'">
                <input
                  v-model="recipientType"
                  type="radio"
                  value="phone"
                  class="mt-1 w-4 h-4"
                />
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <PhoneIcon class="w-5 h-5 text-primary" />
                    <div class="font-semibold text-sm">Numéro</div>
                  </div>
                  <div class="text-xs text-muted-foreground mt-1">Saisir manuellement</div>
                </div>
              </label>

              <label class="flex items-start gap-3 p-4 border-2 rounded-lg cursor-pointer hover:bg-accent/50 transition-colors"
                :class="recipientType === 'multiple' ? 'border-primary bg-primary/5' : 'border-border'">
                <input
                  v-model="recipientType"
                  type="radio"
                  value="multiple"
                  class="mt-1 w-4 h-4"
                />
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <UsersIcon class="w-5 h-5 text-primary" />
                    <div class="font-semibold text-sm">Plusieurs</div>
                  </div>
                  <div class="text-xs text-muted-foreground mt-1">Multiples numéros</div>
                </div>
              </label>
            </div>
          </div>

          <!-- Contact selection -->
          <div v-if="recipientType === 'contact'" class="space-y-2">
            <label class="text-sm font-medium">Sélectionner un contact *</label>
            <select
              v-model="selectedContact"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
            >
              <option value="">Choisir un contact...</option>
              <option v-for="contact in contacts" :key="contact.id" :value="contact.id">
                {{ contact.name }} ({{ contact.phone }})
              </option>
            </select>
          </div>

          <!-- Single phone number -->
          <div v-if="recipientType === 'phone'" class="space-y-2">
            <label class="text-sm font-medium flex items-center gap-2">
              <PhoneIcon class="w-4 h-4 text-muted-foreground" />
              Numéro de téléphone *
            </label>
            <input
              v-model="phoneNumber"
              type="tel"
              placeholder="+241 77 75 07 37 ou 77750737"
              @input="validatePhoneNumber"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              :class="phoneValidation.phone && !phoneValidation.phone.valid ? 'border-destructive' : ''"
            />
            <div v-if="phoneValidation.phone" class="space-y-1">
              <p v-if="phoneValidation.phone.valid" class="flex items-center gap-1.5 text-xs text-success">
                <CheckCircleIcon class="w-4 h-4" />
                <span>{{ phoneValidation.phone.operator_name }} ({{ phoneValidation.phone.prefix }}) - Format: {{ phoneValidation.phone.formatted }}</span>
              </p>
              <p v-else class="flex items-center gap-1.5 text-xs text-destructive">
                <ExclamationCircleIcon class="w-4 h-4" />
                <span>{{ phoneValidation.phone.message }}</span>
              </p>
            </div>
            <p class="text-xs text-muted-foreground">
              Format: +241 XX XX XX XX ou XX XX XX XX<br/>
              Airtel: 77, 74, 76 | Moov: 60, 62, 65, 66
            </p>
          </div>

          <!-- Multiple phone numbers -->
          <div v-if="recipientType === 'multiple'" class="space-y-2">
            <label class="text-sm font-medium flex items-center gap-2">
              <UsersIcon class="w-4 h-4 text-muted-foreground" />
              Numéros de téléphone (un par ligne) *
            </label>
            <textarea
              v-model="phoneNumbers"
              @input="validateMultiplePhones"
              placeholder="+241 77 75 07 37&#10;77 75 07 37&#10;62 34 56 78"
              rows="5"
              class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 resize-none font-mono"
            ></textarea>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
              <div class="flex items-center gap-1.5">
                <div class="w-2 h-2 rounded-full bg-primary"></div>
                <span><strong>{{ phoneNumbersCount }}</strong> total</span>
              </div>
              <div v-if="phoneValidation.multiple.airtel > 0" class="flex items-center gap-1.5">
                <div class="w-2 h-2 rounded-full bg-red-500"></div>
                <span><strong>{{ phoneValidation.multiple.airtel }}</strong> Airtel</span>
              </div>
              <div v-if="phoneValidation.multiple.moov > 0" class="flex items-center gap-1.5">
                <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                <span><strong>{{ phoneValidation.multiple.moov }}</strong> Moov</span>
              </div>
              <div v-if="phoneValidation.multiple.invalid > 0" class="flex items-center gap-1.5">
                <div class="w-2 h-2 rounded-full bg-destructive"></div>
                <span><strong>{{ phoneValidation.multiple.invalid }}</strong> invalide(s)</span>
              </div>
            </div>
            <p class="text-xs text-muted-foreground">
              Airtel: 77, 74, 76 | Moov: 60, 62, 65, 66
            </p>
          </div>

          <!-- Message -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <label class="text-sm font-medium flex items-center gap-2">
                <ChatBubbleLeftIcon class="w-4 h-4 text-muted-foreground" />
                Message *
              </label>
              <div class="flex items-center gap-3 text-xs">
                <span class="text-muted-foreground">{{ messageLength }}/160 caractères</span>
                <span v-if="smsCount > 1" class="px-2 py-1 bg-warning/10 text-warning rounded font-medium">
                  {{ smsCount }} SMS
                </span>
              </div>
            </div>
            <textarea
              v-model="message"
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

          <!-- Estimated cost -->
          <div class="p-5 bg-gradient-to-r from-primary/10 to-primary/5 border-2 border-primary/20 rounded-lg">
            <div class="flex items-start gap-4">
              <ChartPieIcon class="w-10 h-10 text-primary flex-shrink-0 mt-1" />
              <div class="flex-1">
                <p class="font-bold text-lg mb-2">Estimation de l'envoi</p>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
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
                    <p class="text-2xl font-bold text-foreground">{{ totalSMS }}</p>
                  </div>
                  <div class="md:col-span-3">
                    <p class="text-muted-foreground">Coût estimé</p>
                    <p class="text-3xl font-bold text-primary">{{ estimatedCost }} XAF</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Success/Error Messages -->
          <div v-if="successMessage" class="rounded-lg bg-success/10 border border-success/20 p-4">
            <div class="flex items-center gap-2 text-success">
              <CheckCircleIcon class="w-5 h-5" />
              <p class="font-medium">{{ successMessage }}</p>
            </div>
          </div>

          <div v-if="errorMessage" class="rounded-lg bg-destructive/10 border border-destructive/20 p-4">
            <div class="flex items-center gap-2 text-destructive">
              <ExclamationCircleIcon class="w-5 h-5" />
              <p class="font-medium">{{ errorMessage }}</p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex gap-3 pt-4 border-t">
            <button
              @click="sendMessage"
              :disabled="!canSend || sending"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-success text-success-foreground hover:bg-success/90 h-10 px-6 py-2"
            >
              <PaperAirplaneIcon v-if="!sending" class="w-5 h-5" />
              <div v-else class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
              <span>{{ sending ? 'Envoi en cours...' : 'Envoyer maintenant' }}</span>
            </button>
            <button
              @click="resetForm"
              :disabled="sending"
              class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2"
            >
              <XCircleIcon class="w-4 h-4" />
              <span>Réinitialiser</span>
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
  PaperAirplaneIcon,
  UserIcon,
  PhoneIcon,
  UsersIcon,
  ChatBubbleLeftIcon,
  ChartPieIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon
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

const recipientType = ref<'contact' | 'phone' | 'multiple'>('contact')
const selectedContact = ref('')
const phoneNumber = ref('')
const phoneNumbers = ref('')
const message = ref('')
const contacts = ref<Contact[]>([])
const sending = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const phoneValidation = ref<{
  phone: any
  multiple: { airtel: number, moov: number, invalid: number }
}>({
  phone: null,
  multiple: { airtel: 0, moov: 0, invalid: 0 }
})

// Fonction pour valider et détecter l'opérateur d'un numéro
function detectOperator(phone: string) {
  const cleaned = phone.replace(/\D/g, '')
  let number = cleaned

  if (number.startsWith('241')) {
    number = number.substring(3)
  }

  if (number.length !== 8) {
    return {
      valid: false,
      message: 'Le numéro doit contenir 8 chiffres',
      operator: 'unknown'
    }
  }

  const prefix = number.substring(0, 2)
  const airtelPrefixes = ['77', '74', '76']
  const moovPrefixes = ['60', '62', '65', '66']

  if (airtelPrefixes.includes(prefix)) {
    return {
      valid: true,
      operator: 'airtel',
      operator_name: 'Airtel Gabon',
      prefix: prefix,
      formatted: `+241 ${number.substring(0, 2)} ${number.substring(2, 4)} ${number.substring(4, 6)} ${number.substring(6, 8)}`
    }
  }

  if (moovPrefixes.includes(prefix)) {
    return {
      valid: true,
      operator: 'moov',
      operator_name: 'Moov Gabon',
      prefix: prefix,
      formatted: `+241 ${number.substring(0, 2)} ${number.substring(2, 4)} ${number.substring(4, 6)} ${number.substring(6, 8)}`
    }
  }

  return {
    valid: false,
    message: `Préfixe ${prefix} non reconnu (Airtel: 77, 74, 76 | Moov: 60, 62, 65, 66)`,
    operator: 'unknown'
  }
}

function validatePhoneNumber() {
  if (!phoneNumber.value.trim()) {
    phoneValidation.value.phone = null
    return
  }

  phoneValidation.value.phone = detectOperator(phoneNumber.value)
}

function validateMultiplePhones() {
  const numbers = phoneNumbers.value.split('\n').filter(n => n.trim())
  const stats = { airtel: 0, moov: 0, invalid: 0 }

  numbers.forEach(num => {
    const result = detectOperator(num)
    if (result.operator === 'airtel') stats.airtel++
    else if (result.operator === 'moov') stats.moov++
    else stats.invalid++
  })

  phoneValidation.value.multiple = stats
}

const messageLength = computed(() => message.value.length)
const smsCount = computed(() => Math.ceil(message.value.length / 160) || 1)

const phoneNumbersCount = computed(() => {
  if (recipientType.value === 'multiple' && phoneNumbers.value) {
    return phoneNumbers.value.split('\n').filter(n => n.trim()).length
  }
  return 0
})

const estimatedRecipients = computed(() => {
  if (recipientType.value === 'contact' && selectedContact.value) return 1
  if (recipientType.value === 'phone' && phoneNumber.value) return 1
  if (recipientType.value === 'multiple') return phoneNumbersCount.value
  return 0
})

const totalSMS = computed(() => estimatedRecipients.value * smsCount.value)

const estimatedCost = computed(() => {
  const costPerSMS = 20 // 20 FCFA par SMS
  return Math.round(totalSMS.value * costPerSMS)
})

const canSend = computed(() => {
  if (!message.value.trim()) return false

  if (recipientType.value === 'contact' && !selectedContact.value) return false
  if (recipientType.value === 'phone' && !phoneNumber.value.trim()) return false
  if (recipientType.value === 'multiple' && phoneNumbersCount.value === 0) return false

  return true
})

async function loadContacts() {
  try {
    const data = await contactService.getAll()
    contacts.value = data.filter((c: Contact) => c.status === 'active')
  } catch (error) {
    console.error('Error loading contacts:', error)
  }
}

async function sendMessage() {
  if (!canSend.value) return

  const confirmed = await showConfirm(
    'Confirmer l\'envoi',
    `Envoyer ${totalSMS.value} SMS (${estimatedRecipients.value} destinataire(s) × ${smsCount.value} SMS) pour ${estimatedCost.value} XAF ?`
  )

  if (!confirmed) return

  sending.value = true
  successMessage.value = ''
  errorMessage.value = ''

  try {
    let recipients: string[] = []

    if (recipientType.value === 'contact' && selectedContact.value) {
      const contact = contacts.value.find(c => c.id.toString() === selectedContact.value)
      if (contact) {
        recipients = [contact.phone]
      }
    } else if (recipientType.value === 'phone' && phoneNumber.value) {
      recipients = [phoneNumber.value.trim()]
    } else if (recipientType.value === 'multiple' && phoneNumbers.value) {
      recipients = phoneNumbers.value.split('\n').map(n => n.trim()).filter(n => n)
    }

    const data = {
      recipients,
      message: message.value,
      type: 'immediate'
    }

    await apiClient.post('/messages/send', data)

    showSuccess(`${totalSMS.value} SMS envoyé(s) avec succès à ${estimatedRecipients.value} destinataire(s) !`)

    // Reset form after success
    setTimeout(() => {
      resetForm()
    }, 2000)
  } catch (error: any) {
    console.error('Error sending message:', error)
    showError(error.response?.data?.message || 'Erreur lors de l\'envoi du message')
  } finally {
    sending.value = false
  }
}

function resetForm() {
  recipientType.value = 'contact'
  selectedContact.value = ''
  phoneNumber.value = ''
  phoneNumbers.value = ''
  message.value = ''
  successMessage.value = ''
  errorMessage.value = ''
}

onMounted(() => {
  loadContacts()
})
</script>
