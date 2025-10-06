import axios from 'axios'

// Configure axios for Laravel Sanctum with session-based authentication
axios.defaults.withCredentials = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// Get CSRF token from meta tag
const token = document.head.querySelector('meta[name="csrf-token"]')
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content
}
axios.defaults.baseURL = 'http://127.0.0.1:8000';

window.axios = axios
