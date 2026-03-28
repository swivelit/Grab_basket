backend-verify:
	cd backend && ./scripts/verify_backend.sh

mobile-verify:
	cd mobile && npm run lint

verify:
	$(MAKE) backend-verify
	$(MAKE) mobile-verify
