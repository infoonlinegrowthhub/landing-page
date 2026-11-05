/**
 * Dynamic E-Commerce Promotional Landing Page Scripts
 * Crawler Sandbox Safe: All Browser APIs guarded inside try { ... } catch (e) {}
 */

(() => {
  'use strict';

  // Guard optional ServiceWorker / Storage probing in sandboxed crawler environments
  try {
    if (typeof navigator !== 'undefined' && 'serviceWorker' in navigator) {
      // Sandboxed crawler guard
    }
  } catch (e) {}

  document.addEventListener('DOMContentLoaded', () => {
    // 1. Live Promo Countdown Timer
    try {
      const countdownEl = document.getElementById('promoCountdown');
      if (countdownEl && countdownEl.dataset.end) {
        const endTime = new Date(countdownEl.dataset.end.replace(' ', 'T')).getTime();
        const updateTimer = () => {
          try {
            const now = Date.now();
            const diff = endTime - now;
            if (diff <= 0 || Number.isNaN(diff)) {
              countdownEl.textContent = 'OFFER ENDING SOON';
              return;
            }
            const d = Math.floor(diff / (1000 * 60 * 60 * 24));
            const h = Math.floor((diff / (1000 * 60 * 60)) % 24);
            const m = Math.floor((diff / 1000 / 60) % 60);
            const s = Math.floor((diff / 1000) % 60);
            countdownEl.textContent = `${d}d : ${String(h).padStart(2, '0')}h : ${String(m).padStart(2, '0')}m : ${String(s).padStart(2, '0')}s`;
          } catch (e) {}
        };
        updateTimer();
        setInterval(updateTimer, 1000);
      }
    } catch (e) {}

    // 2. Spotlight Gallery Thumbnail Switcher
    try {
      const spotlightMainImg = document.getElementById('spotlightMainImage');
      const spotlightThumbs = document.querySelectorAll('.spotlight-thumb');
      spotlightThumbs.forEach((thumb) => {
        thumb.addEventListener('click', () => {
          try {
            if (spotlightMainImg && thumb.dataset.full) {
              spotlightMainImg.src = thumb.dataset.full;
            }
            spotlightThumbs.forEach((t) => t.classList.remove('active'));
            thumb.classList.add('active');
          } catch (e) {}
        });
      });
    } catch (e) {}

    // 3. Category Tabs & Instant Product Search Filter
    try {
      const catButtons = document.querySelectorAll('.category-pill-btn');
      const productItems = document.querySelectorAll('.product-grid-item');
      const searchInput = document.getElementById('productSearchInput');
      const emptyState = document.getElementById('productsEmptyState');

      let activeCat = 'all';

      const filterProducts = () => {
        try {
          const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
          let visibleCount = 0;

          productItems.forEach((item) => {
            const itemCat = item.dataset.category || '';
            const itemTitle = (item.dataset.title || '').toLowerCase();
            const matchesCat = activeCat === 'all' || itemCat === activeCat;
            const matchesSearch = query === '' || itemTitle.includes(query);

            if (matchesCat && matchesSearch) {
              item.classList.remove('d-none');
              visibleCount++;
            } else {
              item.classList.add('d-none');
            }
          });

          if (emptyState) {
            emptyState.classList.toggle('d-none', visibleCount > 0);
          }
        } catch (e) {}
      };

      catButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
          catButtons.forEach((b) => b.classList.remove('active'));
          btn.classList.add('active');
          activeCat = btn.dataset.filter || 'all';
          filterProducts();
        });
      });

      if (searchInput) {
        searchInput.addEventListener('input', filterProducts);
      }
    } catch (e) {}

    // 4. Product Quick-View Modal Population
    try {
      const quickViewModalEl = document.getElementById('quickViewModal');
      if (quickViewModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modalInstance = new bootstrap.Modal(quickViewModalEl);
        document.querySelectorAll('.btn-quick-view').forEach((btn) => {
          btn.addEventListener('click', () => {
            try {
              const data = JSON.parse(btn.dataset.product || '{}');
              document.getElementById('qvTitle').textContent = data.name || '';
              document.getElementById('qvSubtitle').textContent = data.subtitle || '';
              document.getElementById('qvCategory').textContent = data.category_name || 'Featured';
              document.getElementById('qvPlatform').textContent = data.platform_label || 'Official Link';
              document.getElementById('qvPrice').textContent = data.price_formatted || '';

              const qvCompare = document.getElementById('qvComparePrice');
              if (data.compare_formatted) {
                qvCompare.textContent = data.compare_formatted;
                qvCompare.classList.remove('d-none');
              } else {
                qvCompare.classList.add('d-none');
              }

              const qvDiscount = document.getElementById('qvDiscountBadge');
              if (qvDiscount) {
                if (data.discount_badge) {
                  qvDiscount.textContent = data.discount_badge;
                  qvDiscount.classList.remove('d-none');
                } else {
                  qvDiscount.classList.add('d-none');
                }
              }

              const qvStock = document.getElementById('qvStockBadge');
              if (qvStock) {
                qvStock.textContent = data.stock_status || 'In Stock';
              }

              const qvRating = document.getElementById('qvRatingBadge');
              if (qvRating) {
                qvRating.textContent = `★ ${data.rating || '4.9'} (${data.reviews_count || 100})`;
              }

              document.getElementById('qvDescription').textContent = data.full_description || data.short_description || '';
              const qvMainImg = document.getElementById('qvMainImage');
              qvMainImg.src = data.main_image_resolved || '';
              qvMainImg.alt = data.name || 'Product';

              const thumbsWrap = document.getElementById('qvGalleryThumbs');
              if (thumbsWrap) {
                thumbsWrap.innerHTML = '';
                const gallery = Array.isArray(data.gallery) ? data.gallery : [];
                if (gallery.length > 1) {
                  gallery.forEach((imgUrl, idx) => {
                    const thumb = document.createElement('img');
                    thumb.src = imgUrl;
                    thumb.alt = `${data.name || 'Product'} view ${idx + 1}`;
                    thumb.className = `qv-thumb ${idx === 0 ? 'active' : ''}`;
                    thumb.addEventListener('click', () => {
                      qvMainImg.src = imgUrl;
                      thumbsWrap.querySelectorAll('.qv-thumb').forEach((t) => t.classList.remove('active'));
                      thumb.classList.add('active');
                    });
                    thumbsWrap.appendChild(thumb);
                  });
                }
              }

              const featList = document.getElementById('qvFeaturesList');
              featList.innerHTML = '';
              (data.features || []).forEach((feat) => {
                const li = document.createElement('li');
                li.className = 'mb-1 d-flex align-items-start gap-2';
                li.innerHTML = `<i class="bi bi-check-circle-fill text-success mt-1"></i><span>${feat}</span>`;
                featList.appendChild(li);
              });

              const qvBuyBtn = document.getElementById('qvBuyButton');
              qvBuyBtn.href = data.slug ? `buy/${encodeURIComponent(data.slug)}?src=modal` : `go?id=${encodeURIComponent(data.id)}&src=modal`;
              qvBuyBtn.innerHTML = `<i class="bi bi-bag-check-fill me-2"></i>${data.buy_button_text || 'Buy Now'} <i class="bi bi-box-arrow-up-right ms-1"></i>`;
              if (Number(data.open_in_new_tab) === 1) {
                qvBuyBtn.setAttribute('target', '_blank');
                qvBuyBtn.setAttribute('rel', 'noopener');
              } else {
                qvBuyBtn.removeAttribute('target');
              }

              modalInstance.show();
            } catch (e) {}
          });
        });
      }
    } catch (e) {}

    // 5. Sticky Bottom Conversion Bar on Scroll (Passive Listener)
    try {
      const stickyBar = document.getElementById('stickyConversionBar');
      if (stickyBar) {
        window.addEventListener('scroll', () => {
          try {
            if (window.scrollY > 520) {
              stickyBar.classList.add('visible');
            } else {
              stickyBar.classList.remove('visible');
            }
          } catch (e) {}
        }, { passive: true });
      }
    } catch (e) {}

    // 6. Pre-Sale Inquiry AJAX Form Submission
    try {
      const inquiryForm = document.getElementById('inquiryForm');
      const inquiryFeedback = document.getElementById('inquiryFeedback');
      if (inquiryForm && inquiryFeedback) {
        inquiryForm.addEventListener('submit', async (e) => {
          e.preventDefault();
          const submitBtn = inquiryForm.querySelector('button[type="submit"]');
          const originalText = submitBtn.innerHTML;
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';

          try {
            const formData = new FormData(inquiryForm);
            const resp = await fetch('contact-submit', {
              method: 'POST',
              body: formData
            });
            const result = await resp.json();

            inquiryFeedback.className = `alert mt-3 ${result.success ? 'alert-success' : 'alert-danger'}`;
            inquiryFeedback.textContent = result.message || 'Submitted.';
            inquiryFeedback.classList.remove('d-none');

            if (result.success) {
              inquiryForm.reset();
            }
          } catch (err) {
            inquiryFeedback.className = 'alert alert-danger mt-3';
            inquiryFeedback.textContent = 'Network error. Please try again.';
            inquiryFeedback.classList.remove('d-none');
          } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
          }
        });
      }
    } catch (e) {}
  });
})();
