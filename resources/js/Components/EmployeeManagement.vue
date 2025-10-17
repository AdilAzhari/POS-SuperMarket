<template>
  <div class="space-y-6">
    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Employee Management</h2>
        <p class="text-gray-600 mt-1">Manage staff members and their permissions</p>
      </div>
      
      <div class="flex flex-col sm:flex-row gap-3">
        <!-- Search Bar -->
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search employees..."
            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 w-full sm:w-64"
          />
          <Search class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
        </div>
        
        <!-- Role Filter -->
        <select 
          v-model="selectedRole"
          class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="all">All Roles</option>
          <option value="admin">Administrator</option>
          <option value="manager">Manager</option>
          <option value="supervisor">Supervisor</option>
          <option value="cashier">Cashier</option>
        </select>

        <!-- Sort Controls -->
        <div class="flex items-center gap-2">
          <select
            v-model="sortBy"
            class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
          >
            <option value="name">Name</option>
            <option value="role">Role</option>
            <option value="created_at">Hire Date</option>
            <option value="email">Email</option>
          </select>
          <button
            @click="toggleSortDirection"
            class="p-2 border border-gray-300 rounded-lg hover:bg-gray-50"
            :title="sortDirection === 'asc' ? 'Sort descending' : 'Sort ascending'"
          >
            <ArrowUpDown class="w-4 h-4" :class="sortDirection === 'desc' ? 'rotate-180' : ''" />
          </button>
        </div>
        
        <!-- Add Employee Button -->
        <button
          @click="openCreateForm"
          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 whitespace-nowrap"
        >
          <UserPlus class="h-5 w-5" />
          Add Employee
        </button>
      </div>
    </div>

    <!-- Employee Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
        <div class="flex items-center">
          <div class="p-2 bg-blue-100 rounded-lg">
            <Users class="h-6 w-6 text-blue-600" />
          </div>
        </div>
        <div class="mt-4">
          <h3 class="text-lg font-medium text-gray-900">{{ employees.length }}</h3>
          <p class="text-sm text-gray-500">Total Employees</p>
        </div>
      </div>
      
      <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
        <div class="flex items-center">
          <div class="p-2 bg-green-100 rounded-lg">
            <UserCheck class="h-6 w-6 text-green-600" />
          </div>
        </div>
        <div class="mt-4">
          <h3 class="text-lg font-medium text-gray-900">{{ activeEmployees }}</h3>
          <p class="text-sm text-gray-500">Active Staff</p>
        </div>
      </div>
      
      <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
        <div class="flex items-center">
          <div class="p-2 bg-purple-100 rounded-lg">
            <Shield class="h-6 w-6 text-purple-600" />
          </div>
        </div>
        <div class="mt-4">
          <h3 class="text-lg font-medium text-gray-900">{{ managersCount }}</h3>
          <p class="text-sm text-gray-500">Managers & Admins</p>
        </div>
      </div>
      
      <div class="bg-white p-6 rounded-lg shadow border border-gray-200">
        <div class="flex items-center">
          <div class="p-2 bg-yellow-100 rounded-lg">
            <Clock class="h-6 w-6 text-yellow-600" />
          </div>
        </div>
        <div class="mt-4">
          <h3 class="text-lg font-medium text-gray-900">{{ recentHires }}</h3>
          <p class="text-sm text-gray-500">New This Month</p>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow p-8">
      <div class="flex items-center justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <span class="ml-3 text-gray-600">Loading employees...</span>
      </div>
    </div>

    <!-- Employees Table -->
    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hire Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="employee in paginatedEmployees" :key="employee.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="h-10 w-10 flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                      <span class="text-white font-medium text-sm">
                        {{ getInitials(employee.name) }}
                      </span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ employee.name }}</div>
                    <div class="text-sm text-gray-500">{{ employee.employee_id || 'No ID' }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getRoleBadgeClass(employee.role)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                  {{ employee.role_label }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <div>{{ employee.email }}</div>
                <div class="text-gray-500">{{ employee.phone || 'No phone' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="employee.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" 
                      class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                  {{ employee.status_label }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ employee.hire_date ? formatDate(employee.hire_date) : 'Not set' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center space-x-2">
                  <button
                    @click="viewEmployee(employee)"
                    class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded text-xs"
                    title="View Details"
                  >
                    View
                  </button>
                  <button
                    @click="editEmployee(employee)"
                    class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-2 py-1 rounded text-xs"
                    title="Edit Employee"
                  >
                    Edit
                  </button>
                  <button
                    @click="resetPassword(employee)"
                    class="text-yellow-600 hover:text-yellow-900 bg-yellow-50 hover:bg-yellow-100 px-2 py-1 rounded text-xs"
                    title="Reset Password"
                  >
                    Reset PWD
                  </button>
                  <button
                    v-if="employee.is_active"
                    @click="confirmDeactivate(employee)"
                    class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-2 py-1 rounded text-xs"
                    title="Deactivate Employee"
                  >
                    Deactivate
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
        <div class="text-sm text-gray-500">
          Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to {{ Math.min(currentPage * itemsPerPage, filteredEmployees.length) }} of {{ filteredEmployees.length }} employees
        </div>
        <div class="flex items-center space-x-2">
          <button
            @click="currentPage = Math.max(1, currentPage - 1)"
            :disabled="currentPage === 1"
            class="px-3 py-1 border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          <div class="flex space-x-1">
            <button
              v-for="page in visiblePages"
              :key="page"
              @click="currentPage = page"
              :class="[
                'px-3 py-1 border rounded-lg',
                page === currentPage
                  ? 'bg-blue-600 text-white border-blue-600'
                  : 'hover:bg-gray-50'
              ]"
            >
              {{ page }}
            </button>
          </div>
          <button
            @click="currentPage = Math.min(totalPages, currentPage + 1)"
            :disabled="currentPage === totalPages"
            class="px-3 py-1 border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredEmployees.length === 0" class="text-center py-12">
        <Users class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">
          {{ employees.length === 0 ? 'No employees' : 'No employees match your search' }}
        </h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ employees.length === 0 ? 'Get started by adding a new employee.' : 'Try adjusting your search criteria.' }}
        </p>
      </div>
    </div>

    <!-- Create/Edit Form Modal -->
    <div v-if="showForm" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white max-h-[80vh] overflow-y-auto">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ isEditing ? 'Edit Employee' : 'Add New Employee' }}
          </h3>
          
          <form @submit.prevent="submitForm" class="space-y-4">
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700">Full Name *</label>
              <input
                v-model="form.name"
                type="text"
                id="name"
                required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.name }"
              />
              <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
            </div>

            <div>
              <label for="email" class="block text-sm font-medium text-gray-700">Email *</label>
              <input
                v-model="form.email"
                type="email"
                id="email"
                required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.email }"
              />
              <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email[0] }}</p>
            </div>

            <div v-if="!isEditing">
              <label for="password" class="block text-sm font-medium text-gray-700">Password *</label>
              <input
                v-model="form.password"
                type="password"
                id="password"
                :required="!isEditing"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.password }"
              />
              <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password[0] }}</p>
            </div>

            <div>
              <label for="role" class="block text-sm font-medium text-gray-700">Role *</label>
              <select
                v-model="form.role"
                id="role"
                required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.role }"
              >
                <option value="">Select role</option>
                <option value="admin">Administrator</option>
                <option value="manager">Manager</option>
                <option value="supervisor">Supervisor</option>
                <option value="cashier">Cashier</option>
              </select>
              <p v-if="errors.role" class="mt-1 text-sm text-red-600">{{ errors.role[0] }}</p>
            </div>

            <div>
              <label for="employee_id" class="block text-sm font-medium text-gray-700">Employee ID</label>
              <input
                v-model="form.employee_id"
                type="text"
                id="employee_id"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.employee_id }"
                placeholder="Auto-generated if empty"
              />
              <p v-if="errors.employee_id" class="mt-1 text-sm text-red-600">{{ errors.employee_id[0] }}</p>
            </div>

            <div>
              <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
              <input
                v-model="form.phone"
                type="tel"
                id="phone"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.phone }"
              />
              <p v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone[0] }}</p>
            </div>

            <div>
              <label for="hourly_rate" class="block text-sm font-medium text-gray-700">Hourly Rate</label>
              <input
                v-model="form.hourly_rate"
                type="number"
                step="0.01"
                min="0"
                id="hourly_rate"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.hourly_rate }"
              />
              <p v-if="errors.hourly_rate" class="mt-1 text-sm text-red-600">{{ errors.hourly_rate[0] }}</p>
            </div>

            <div>
              <label for="hire_date" class="block text-sm font-medium text-gray-700">Hire Date</label>
              <input
                v-model="form.hire_date"
                type="date"
                id="hire_date"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                :class="{ 'border-red-500': errors.hire_date }"
              />
              <p v-if="errors.hire_date" class="mt-1 text-sm text-red-600">{{ errors.hire_date[0] }}</p>
            </div>

            <div v-if="isEditing">
              <label class="flex items-center">
                <input
                  v-model="form.is_active"
                  type="checkbox"
                  class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                />
                <span class="ml-2 text-sm text-gray-700">Active Employee</span>
              </label>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
              <button
                type="button"
                @click="closeForm"
                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
              >
                {{ loading ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Employee Details Modal -->
    <div v-if="showDetailsModal && selectedEmployee" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-start mb-4">
          <h3 class="text-xl font-bold text-gray-900">Employee Details</h3>
          <button
            @click="showDetailsModal = false"
            class="text-gray-400 hover:text-gray-600"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Employee Profile Header -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 mb-6">
          <div class="flex items-center space-x-4">
            <div class="h-20 w-20 rounded-full bg-white flex items-center justify-center">
              <span class="text-blue-600 font-bold text-2xl">
                {{ getInitials(selectedEmployee.name) }}
              </span>
            </div>
            <div class="text-white">
              <h2 class="text-2xl font-bold">{{ selectedEmployee.name }}</h2>
              <p class="text-blue-100">{{ selectedEmployee.role_label }}</p>
              <p class="text-sm text-blue-100 mt-1">{{ selectedEmployee.employee_id || 'No ID' }}</p>
            </div>
            <div class="ml-auto">
              <span
                :class="selectedEmployee.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                class="px-3 py-1 text-sm font-semibold rounded-full"
              >
                {{ selectedEmployee.status_label }}
              </span>
            </div>
          </div>
        </div>

        <!-- Employee Information Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <!-- Contact Information -->
          <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Contact Information</h4>
            <div class="space-y-3">
              <div>
                <label class="text-xs text-gray-500">Email</label>
                <p class="text-sm text-gray-900">{{ selectedEmployee.email }}</p>
              </div>
              <div>
                <label class="text-xs text-gray-500">Phone</label>
                <p class="text-sm text-gray-900">{{ selectedEmployee.phone || 'Not provided' }}</p>
              </div>
              <div v-if="selectedEmployee.address">
                <label class="text-xs text-gray-500">Address</label>
                <p class="text-sm text-gray-900">{{ selectedEmployee.address }}</p>
              </div>
            </div>
          </div>

          <!-- Employment Details -->
          <div class="bg-white border border-gray-200 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Employment Details</h4>
            <div class="space-y-3">
              <div>
                <label class="text-xs text-gray-500">Role</label>
                <p class="text-sm text-gray-900">{{ selectedEmployee.role_label }}</p>
              </div>
              <div>
                <label class="text-xs text-gray-500">Hire Date</label>
                <p class="text-sm text-gray-900">
                  {{ selectedEmployee.hire_date ? formatDate(selectedEmployee.hire_date) : 'Not set' }}
                </p>
              </div>
              <div v-if="selectedEmployee.hourly_rate">
                <label class="text-xs text-gray-500">Hourly Rate</label>
                <p class="text-sm text-gray-900">${{ parseFloat(selectedEmployee.hourly_rate).toFixed(2) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Performance Metrics -->
        <div v-if="employeePerformance" class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
          <h4 class="text-sm font-semibold text-gray-500 uppercase mb-4">Performance Metrics</h4>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="text-center p-3 bg-blue-50 rounded-lg">
              <p class="text-2xl font-bold text-blue-600">{{ employeePerformance.total_sales || 0 }}</p>
              <p class="text-xs text-gray-600">Total Sales</p>
            </div>
            <div class="text-center p-3 bg-green-50 rounded-lg">
              <p class="text-2xl font-bold text-green-600">${{ parseFloat(employeePerformance.total_revenue || 0).toFixed(2) }}</p>
              <p class="text-xs text-gray-600">Total Revenue</p>
            </div>
            <div class="text-center p-3 bg-purple-50 rounded-lg">
              <p class="text-2xl font-bold text-purple-600">${{ parseFloat(employeePerformance.average_sale || 0).toFixed(2) }}</p>
              <p class="text-xs text-gray-600">Avg Sale Value</p>
            </div>
          </div>
        </div>

        <!-- Permissions -->
        <div v-if="selectedEmployee.permissions && selectedEmployee.permissions.length > 0" class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
          <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Permissions</h4>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="permission in selectedEmployee.permissions"
              :key="permission"
              class="px-3 py-1 bg-indigo-100 text-indigo-800 text-xs font-medium rounded-full"
            >
              {{ permission.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
            </span>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-3 pt-4 border-t">
          <button
            @click="showDetailsModal = false"
            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            Close
          </button>
          <button
            @click="editEmployee(selectedEmployee); showDetailsModal = false"
            class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700"
          >
            Edit Employee
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Search, UserPlus, Users, UserCheck, Shield, Clock, ArrowUpDown } from 'lucide-vue-next'
import { useNotificationStore } from '@/stores/notifications'
import axios from 'axios'

const notificationStore = useNotificationStore()

const employees = ref([])
const showForm = ref(false)
const isEditing = ref(false)
const loading = ref(false)
const isLoading = ref(false)
const errors = ref({})
const searchQuery = ref('')
const selectedRole = ref('all')
const showDetailsModal = ref(false)
const selectedEmployee = ref(null)
const employeePerformance = ref(null)

// Sorting and pagination
const sortBy = ref('name')
const sortDirection = ref('asc')
const currentPage = ref(1)
const itemsPerPage = ref(20)

const form = ref({
  name: '',
  email: '',
  password: '',
  role: '',
  employee_id: '',
  phone: '',
  address: '',
  hourly_rate: '',
  hire_date: '',
  is_active: true
})

// Computed properties
const filteredEmployees = computed(() => {
  let filtered = employees.value

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(employee =>
      employee.name.toLowerCase().includes(query) ||
      employee.email.toLowerCase().includes(query) ||
      (employee.employee_id && employee.employee_id.toLowerCase().includes(query)) ||
      (employee.phone && employee.phone.includes(query))
    )
  }

  // Apply role filter
  if (selectedRole.value !== 'all') {
    filtered = filtered.filter(employee => employee.role === selectedRole.value)
  }

  // Apply sorting
  filtered.sort((a, b) => {
    let aValue = a[sortBy.value]
    let bValue = b[sortBy.value]
    
    // Handle null/undefined values
    if (aValue == null) aValue = ''
    if (bValue == null) bValue = ''
    
    // Handle date sorting
    if (sortBy.value === 'created_at') {
      aValue = new Date(aValue || 0)
      bValue = new Date(bValue || 0)
    } else {
      // String comparison
      aValue = aValue.toString().toLowerCase()
      bValue = bValue.toString().toLowerCase()
    }
    
    if (sortDirection.value === 'asc') {
      return aValue < bValue ? -1 : aValue > bValue ? 1 : 0
    } else {
      return aValue > bValue ? -1 : aValue < bValue ? 1 : 0
    }
  })

  return filtered
})

const totalPages = computed(() => Math.ceil(filteredEmployees.value.length / itemsPerPage.value))

const paginatedEmployees = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredEmployees.value.slice(start, end)
})

const visiblePages = computed(() => {
  const total = totalPages.value
  const current = currentPage.value
  const delta = 2
  
  let start = Math.max(1, current - delta)
  let end = Math.min(total, current + delta)
  
  if (end - start < 2 * delta) {
    start = Math.max(1, end - 2 * delta)
    end = Math.min(total, start + 2 * delta)
  }
  
  const pages = []
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  return pages
})

const activeEmployees = computed(() => {
  return employees.value.filter(employee => employee.is_active).length
})

const managersCount = computed(() => {
  return employees.value.filter(employee => ['admin', 'manager'].includes(employee.role)).length
})

const recentHires = computed(() => {
  const oneMonthAgo = new Date()
  oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1)
  
  return employees.value.filter(employee => {
    if (!employee.hire_date) return false
    const hireDate = new Date(employee.hire_date)
    return hireDate >= oneMonthAgo
  }).length
})

// Helper functions
const getInitials = (name) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getRoleBadgeClass = (role) => {
  const classes = {
    admin: 'bg-red-100 text-red-800',
    manager: 'bg-purple-100 text-purple-800',
    supervisor: 'bg-blue-100 text-blue-800',
    cashier: 'bg-green-100 text-green-800'
  }
  return classes[role] || 'bg-gray-100 text-gray-800'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

// Sorting function
const toggleSortDirection = () => {
  sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
}

// Form functions
const openCreateForm = () => {
  resetForm()
  isEditing.value = false
  showForm.value = true
}

const editEmployee = (employee) => {
  form.value = {
    ...employee,
    role: typeof employee.role === 'object' ? employee.role.value : employee.role,
    password: '', // Don't populate password field
    hire_date: employee.hire_date ? employee.hire_date.split('T')[0] : '',
    hourly_rate: employee.hourly_rate || '',
    phone: employee.phone || '',
    employee_id: employee.employee_id || '',
    address: employee.address || ''
  }
  isEditing.value = true
  showForm.value = true
  errors.value = {}
}

const closeForm = () => {
  showForm.value = false
  resetForm()
  errors.value = {}
}

const resetForm = () => {
  form.value = {
    name: '',
    email: '',
    password: '',
    role: '',
    employee_id: '',
    phone: '',
    address: '',
    hourly_rate: '',
    hire_date: '',
    is_active: true
  }
}

const submitForm = async () => {
  loading.value = true
  errors.value = {}

  try {
    const endpoint = isEditing.value ? `/api/employees/${form.value.id}` : '/api/employees'
    const method = isEditing.value ? 'put' : 'post'

    // Clean form data - only send valid fields
    const cleanedData = {
      name: form.value.name,
      email: form.value.email,
      role: form.value.role,
      is_active: form.value.is_active
    }

    // Add password only if provided
    if (form.value.password && form.value.password.trim() !== '') {
      cleanedData.password = form.value.password
    }

    // Add optional fields only if they have values
    if (form.value.employee_id && form.value.employee_id.trim() !== '') {
      cleanedData.employee_id = form.value.employee_id
    }
    if (form.value.phone && form.value.phone.trim() !== '') {
      cleanedData.phone = form.value.phone
    }
    if (form.value.address && form.value.address.trim() !== '') {
      cleanedData.address = form.value.address
    }
    if (form.value.hourly_rate && form.value.hourly_rate !== '') {
      cleanedData.hourly_rate = form.value.hourly_rate
    }
    if (form.value.hire_date && form.value.hire_date !== '') {
      cleanedData.hire_date = form.value.hire_date
    }

    const response = await axios[method](endpoint, cleanedData)

    notificationStore.success(
      'Success!',
      `Employee ${isEditing.value ? 'updated' : 'created'} successfully`
    )

    closeForm()
    await fetchEmployees()
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      notificationStore.error(
        'Error',
        error.response?.data?.message || `Failed to ${isEditing.value ? 'update' : 'create'} employee`
      )
    }
  } finally {
    loading.value = false
  }
}

const confirmDeactivate = async (employee) => {
  const confirmed = confirm(`Are you sure you want to deactivate "${employee.name}"? They will no longer be able to log in.`)

  if (confirmed) {
    try {
      await axios.delete(`/api/employees/${employee.id}`)
      notificationStore.success('Success!', 'Employee deactivated successfully')
      await fetchEmployees()
    } catch (error) {
      notificationStore.error('Error', 'Failed to deactivate employee')
    }
  }
}

const resetPassword = async (employee) => {
  const newPassword = prompt('Enter new password (leave blank for auto-generated):')
  
  try {
    const response = await axios.post(`/api/employees/${employee.id}/reset-password`, {
      password: newPassword || null
    })
    
    const generatedPassword = response.data.new_password
    notificationStore.success(
      'Password Reset',
      `New password: ${generatedPassword}`,
      { duration: 10000 }
    )
  } catch (error) {
    notificationStore.error('Error', 'Failed to reset password')
  }
}

const viewEmployee = async (employee) => {
  try {
    isLoading.value = true
    const response = await axios.get(`/api/employees/${employee.id}`)
    selectedEmployee.value = response.data.data.employee
    employeePerformance.value = response.data.data.performance
    showDetailsModal.value = true
  } catch (error) {
    notificationStore.error('Error', 'Failed to load employee details')
  } finally {
    isLoading.value = false
  }
}

const fetchEmployees = async () => {
  isLoading.value = true
  try {
    const params = {
      search: searchQuery.value,
      role: selectedRole.value !== 'all' ? selectedRole.value : null,
      active_only: false
    }
    
    const response = await axios.get('/api/employees', { params })
    employees.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load employees:', error)
    employees.value = []
    notificationStore.error('Error', 'Failed to load employees')
  } finally {
    isLoading.value = false
  }
}

// Watchers
watch([searchQuery, selectedRole], () => {
  // Real-time filtering handled by computed property
})

onMounted(async () => {
  await fetchEmployees()
})
</script>