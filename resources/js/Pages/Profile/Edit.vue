<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DeleteUserForm from './Partials/DeleteUserForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'
import ThemeSettings from '@/Components/ThemeSettings.vue'
import { Head, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { 
  User, 
  Mail, 
  Shield, 
  Settings, 
  Calendar, 
  Clock,
  Award,
  TrendingUp,
  BarChart3
} from 'lucide-vue-next'

const page = usePage()
const currentUser = computed(() => page.props.auth?.user)

// Active tab state
const activeTab = ref('profile')

const tabs = [
  { id: 'profile', name: 'Profile Information', icon: User },
  { id: 'security', name: 'Security', icon: Shield },
  { id: 'activity', name: 'Activity', icon: BarChart3 },
  { id: 'preferences', name: 'Preferences', icon: Settings },
]

defineProps({
  mustVerifyEmail: {
    type: Boolean,
  },
  status: {
    type: String,
  },
})
</script>

<template>
  <Head title="Profile Settings" />

  <AuthenticatedLayout>
    <div class="max-w-6xl mx-auto space-y-8">
      <!-- Profile Header -->
      <div class="bg-gradient-to-r from-blue-600 to-purple-700 dark:from-blue-700 dark:to-purple-800 rounded-xl shadow-lg dark:shadow-2xl p-8 text-white">
        <div class="flex items-start justify-between">
          <div class="flex items-center space-x-6">
            <!-- Avatar -->
            <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center text-3xl font-bold backdrop-blur-sm">
              {{ (currentUser?.name || 'U').charAt(0).toUpperCase() }}
            </div>
            
            <!-- User Info -->
            <div>
              <h1 class="text-3xl font-bold">{{ currentUser?.name || 'User' }}</h1>
              <div class="flex items-center space-x-4 mt-2 text-blue-100">
                <div class="flex items-center space-x-1">
                  <Mail class="h-4 w-4" />
                  <span>{{ currentUser?.email || 'No email' }}</span>
                </div>
                <div class="flex items-center space-x-1">
                  <Award class="h-4 w-4" />
                  <span class="capitalize">{{ currentUser?.role || 'User' }}</span>
                </div>
              </div>
              <div class="flex items-center space-x-1 mt-2 text-blue-200 text-sm">
                <Calendar class="h-4 w-4" />
                <span>Member since {{ new Date(currentUser?.created_at || Date.now()).toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) }}</span>
              </div>
            </div>
          </div>
          
          <!-- Quick Stats -->
          <div class="hidden md:flex space-x-6">
            <div class="text-center">
              <div class="text-2xl font-bold">{{ currentUser?.sales_count || '0' }}</div>
              <div class="text-blue-200 text-sm">Sales</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold">{{ currentUser?.login_count || '0' }}</div>
              <div class="text-blue-200 text-sm">Logins</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold">
                <div class="flex items-center space-x-1">
                  <Clock class="h-5 w-5" />
                  <span>Active</span>
                </div>
              </div>
              <div class="text-blue-200 text-sm">Status</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <nav class="flex space-x-8 px-6" aria-label="Tabs">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              activeTab === tab.id
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center space-x-2'
            ]"
          >
            <component :is="tab.icon" class="h-4 w-4" />
            <span>{{ tab.name }}</span>
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div class="space-y-6">
        <!-- Profile Information Tab -->
        <div v-show="activeTab === 'profile'" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
          <div class="max-w-2xl">
            <div class="mb-6">
              <h2 class="text-lg font-semibold text-gray-900 flex items-center space-x-2">
                <User class="h-5 w-5" />
                <span>Profile Information</span>
              </h2>
              <p class="text-sm text-gray-600 mt-1">Update your account's profile information and email address.</p>
            </div>
            <UpdateProfileInformationForm
              :must-verify-email="mustVerifyEmail"
              :status="status"
            />
          </div>
        </div>

        <!-- Security Tab -->
        <div v-show="activeTab === 'security'" class="space-y-6">
          <!-- Update Password -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8">
            <div class="max-w-2xl">
              <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center space-x-2">
                  <Shield class="h-5 w-5" />
                  <span>Update Password</span>
                </h2>
                <p class="text-sm text-gray-600 mt-1">Ensure your account is using a long, random password to stay secure.</p>
              </div>
              <UpdatePasswordForm />
            </div>
          </div>

          <!-- Delete Account -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8">
            <div class="max-w-2xl">
              <div class="mb-6">
                <h2 class="text-lg font-semibold text-red-600 flex items-center space-x-2">
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 14c-.77.833.192 2.5 1.732 2.5z"></path>
                  </svg>
                  <span>Delete Account</span>
                </h2>
                <p class="text-sm text-gray-600 mt-1">Permanently delete your account and all associated data.</p>
              </div>
              <DeleteUserForm />
            </div>
          </div>
        </div>

        <!-- Activity Tab -->
        <div v-show="activeTab === 'activity'" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
          <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center space-x-2">
              <BarChart3 class="h-5 w-5" />
              <span>Recent Activity</span>
            </h2>
            <p class="text-sm text-gray-600 mt-1">Your recent activities and system interactions.</p>
          </div>
          
          <div class="space-y-4">
            <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
              <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                <User class="h-4 w-4 text-blue-600" />
              </div>
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">Profile updated</p>
                <p class="text-xs text-gray-500">Today at 2:30 PM</p>
              </div>
            </div>
            
            <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
              <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                <Shield class="h-4 w-4 text-green-600" />
              </div>
              <div class="flex-1">
                <p class="text-sm font-medium text-gray-900">Password changed</p>
                <p class="text-xs text-gray-500">2 days ago</p>
              </div>
            </div>
            
            <div class="text-center py-8 text-gray-500">
              <Clock class="h-12 w-12 mx-auto mb-4 text-gray-300" />
              <p>No more activities to show</p>
            </div>
          </div>
        </div>

        <!-- Preferences Tab -->
        <div v-show="activeTab === 'preferences'" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-8">
          <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center space-x-2">
              <Settings class="h-5 w-5" />
              <span>Preferences</span>
            </h2>
            <p class="text-sm text-gray-600 mt-1">Customize your experience and notification settings.</p>
          </div>
          
          <div class="space-y-6">
            <!-- Notification Preferences -->
            <div>
              <h3 class="text-sm font-medium text-gray-900 mb-4">Notifications</h3>
              <div class="space-y-3">
                <label class="flex items-center space-x-3">
                  <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" checked>
                  <span class="text-sm text-gray-700">Email notifications</span>
                </label>
                <label class="flex items-center space-x-3">
                  <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" checked>
                  <span class="text-sm text-gray-700">Low stock alerts</span>
                </label>
                <label class="flex items-center space-x-3">
                  <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                  <span class="text-sm text-gray-700">Sales report summaries</span>
                </label>
              </div>
            </div>

            <!-- Theme Settings -->
            <div>
              <ThemeSettings />
            </div>
            
            <!-- Other Display Preferences -->
            <div>
              <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-4">Language & Region</h3>
              <div class="space-y-3">
                <div>
                  <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Language</label>
                  <select class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-emerald-500 focus:border-emerald-500">
                    <option>English (US)</option>
                    <option>English (UK)</option>
                    <option>Bahasa Malaysia</option>
                    <option>中文 (简体)</option>
                    <option>中文 (繁體)</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Currency</label>
                  <select class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-emerald-500 focus:border-emerald-500">
                    <option>Malaysian Ringgit (RM)</option>
                    <option>US Dollar ($)</option>
                    <option>Singapore Dollar (S$)</option>
                    <option>Thai Baht (฿)</option>
                  </select>
                </div>
                <div>
                  <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Timezone</label>
                  <select class="border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-emerald-500 focus:border-emerald-500">
                    <option>Asia/Kuala_Lumpur (UTC+8)</option>
                    <option>Asia/Singapore (UTC+8)</option>
                    <option>Asia/Bangkok (UTC+7)</option>
                    <option>Asia/Jakarta (UTC+7)</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="pt-4">
              <button class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium">
                Save Preferences
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
