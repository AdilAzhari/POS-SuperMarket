# Pull Request

## 📋 Description
<!-- Provide a clear and detailed description of the changes -->

## 🔄 Type of Change
<!-- Mark all relevant options with an "x" -->
- [ ] 🐛 Bug fix (non-breaking change which fixes an issue)
- [ ] ✨ New feature (non-breaking change which adds functionality)
- [ ] 💥 Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] 📚 Documentation update
- [ ] 🔧 Refactoring (no functional changes, code improvement)
- [ ] 🎨 Style changes (formatting, code style improvements)
- [ ] ⚡ Performance improvements
- [ ] 🧪 Adding/updating tests
- [ ] 🔒 Security improvements
- [ ] 🗃️ Database changes (migrations, seeders)

## 🎯 Scope
<!-- Mark all affected areas with an "x" -->
- [ ] Backend (PHP/Laravel)
- [ ] Frontend (Vue.js/Inertia)
- [ ] Database (Migrations/Models)
- [ ] API Routes
- [ ] Authentication/Authorization
- [ ] UI/UX Components
- [ ] Business Logic/Services
- [ ] Testing
- [ ] Configuration
- [ ] Dependencies

## 🧪 Testing
<!-- Describe the tests performed to verify your changes -->

### Automated Tests
- [ ] Unit tests added/updated
- [ ] Feature tests added/updated
- [ ] All tests passing (`php artisan test`)
- [ ] Code coverage maintained/improved

### Manual Testing
- [ ] Tested in development environment
- [ ] Tested across different browsers (if frontend changes)
- [ ] Tested on different screen sizes (if UI changes)
- [ ] Tested with different user roles (if applicable)
- [ ] Tested edge cases and error scenarios

### Test Commands Run
```bash
# List the test commands you ran
php artisan test
php artisan test --filter=SpecificTest
```

## 💻 Code Quality
<!-- Mark completed items with an "x" -->
- [ ] Code follows Laravel coding standards
- [ ] Ran Laravel Pint (`vendor/bin/pint`)
- [ ] No PHPStan errors (`vendor/bin/phpstan analyse`)
- [ ] Ran Rector (if applicable) (`vendor/bin/rector process`)
- [ ] Self-reviewed the code changes
- [ ] Removed debug statements and commented code
- [ ] No console errors or warnings
- [ ] Followed existing code patterns and conventions

## 📝 Changes Made
<!-- Provide a detailed list of changes -->

### Backend Changes
<!-- List backend changes if applicable -->
-

### Frontend Changes
<!-- List frontend changes if applicable -->
-

### Database Changes
<!-- List database changes if applicable -->
- [ ] New migrations added
- [ ] Seeders updated
- [ ] Factory updates
- [ ] Model relationships modified

### API Changes
<!-- List API changes if applicable -->
- [ ] New endpoints added
- [ ] Existing endpoints modified
- [ ] Breaking API changes

## 📸 Screenshots/Videos
<!-- Add screenshots or videos if your changes affect the UI -->

### Before
<!-- Screenshots of the old behavior/UI -->

### After
<!-- Screenshots of the new behavior/UI -->

## 🔗 Related Issues
<!-- Link to related issues, tickets, or discussions -->
Closes #
Related to #

## ⚠️ Breaking Changes
<!-- Describe any breaking changes and migration steps if applicable -->
- [ ] No breaking changes
- [ ] Breaking changes (describe below)

<!-- If there are breaking changes, describe them here -->

## 📋 Deployment Notes
<!-- Any special deployment considerations or steps -->
- [ ] No special deployment steps required
- [ ] Requires database migration (`php artisan migrate`)
- [ ] Requires cache clear (`php artisan cache:clear`)
- [ ] Requires config cache clear (`php artisan config:clear`)
- [ ] Requires npm build (`npm run build`)
- [ ] Requires environment variable updates
- [ ] Requires dependency updates (`composer install` / `npm install`)

## 📚 Documentation
<!-- Mark completed items with an "x" -->
- [ ] Code is self-documenting with clear naming
- [ ] Added PHPDoc blocks for new methods/classes
- [ ] Updated relevant documentation files
- [ ] Added inline comments for complex logic
- [ ] Updated API documentation (if applicable)

## 🔍 Review Checklist
<!-- For reviewers -->
- [ ] Code changes reviewed
- [ ] Tests reviewed and passing
- [ ] No security vulnerabilities introduced
- [ ] Performance impact considered
- [ ] Database queries optimized (no N+1 queries)
- [ ] Error handling is appropriate
- [ ] User experience is intuitive

## 📝 Additional Notes
<!-- Any additional context, concerns, or information for reviewers -->

## 🙏 Reviewer Focus Areas
<!-- Specific areas where you'd like focused review -->
-

---
**PR Author:** @<!-- your github username -->
**Reviewers:** @<!-- tag reviewers -->
