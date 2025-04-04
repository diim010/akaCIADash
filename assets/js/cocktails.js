(function($) {
    'use strict';

    const CocktailsList = {
        init: function() {
            this.container = $('.live-foundation-cocktails');
            this.grid = $('#cocktail-grid');
            this.loadMoreBtn = $('#load-more-cocktails');
            this.modal = $('#cocktail-modal');
            this.modalBody = $('#cocktail-modal-body');
            this.filterForm = $('.cocktail-filters');
            
            this.page = 1;
            this.limit = parseInt(this.grid.data('limit')) || 12;
            this.category = '';
            this.difficulty = '';
            this.sort = 'title';
            this.loading = false;
            
            this.bindEvents();
            this.loadCocktails();
        },

        bindEvents: function() {
            const self = this;
            
            // Apply filters
            this.filterForm.find('#apply-filters').on('click', function(e) {
                e.preventDefault();
                self.category = self.filterForm.find('#category-filter').val();
                self.difficulty = self.filterForm.find('#difficulty-filter').val();
                self.sort = self.filterForm.find('#sort-filter').val();
                self.page = 1;
                self.grid.empty().data('page', 1);
                self.loadCocktails();
            });
            
            // Load more
            this.loadMoreBtn.on('click', function() {
                self.page++;
                self.loadCocktails();
            });
            
            // View cocktail details
            $(document).on('click', '.view-cocktail', function() {
                const cocktailId = $(this).data('id');
                self.openCocktailModal(cocktailId);
            });
            
            // Close modal
            this.modal.find('.close-modal').on('click', function() {
                self.closeModal();
            });
            
            // Close on click outside
            $(window).on('click', function(e) {
                if ($(e.target).is(self.modal)) {
                    self.closeModal();
                }
            });
            
            // Close on ESC key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && self.modal.is(':visible')) {
                    self.closeModal();
                }
            });
        },

        loadCocktails: function() {
            if (this.loading) return;
            
            const self = this;
            this.loading = true;
            this.grid.find('.cocktail-loading').show();
            this.loadMoreBtn.prop('disabled', true);
            
            $.ajax({
                url: liveFoundationData.ajax_url,
                type: 'POST',
                data: {
                    action: 'filter_cocktails',
                    nonce: liveFoundationData.nonce,
                    category: this.category,
                    difficulty: this.difficulty,
                    sort: this.sort,
                    page: this.page,
                    limit: this.limit
                },
                success: function(response) {
                    if (!response.success) {
                        self.showError(response.data || 'Error loading cocktails');
                        return;
                    }
                    
                    if (self.page === 1) {
                        self.grid.empty();
                    }
                    
                    if (response.data.cocktails.length === 0) {
                        if (self.page === 1) {
                            self.grid.html('<div class="no-results">No cocktails found matching your criteria.</div>');
                        }
                    } else {
                        response.data.cocktails.forEach(function(html) {
                            self.grid.append(html);
                        });
                    }
                    
                    // Update page data
                    self.grid.data('page', self.page);
                    self.grid.data('total', response.data.total);
                    
                    // Show/hide load more button
                    if (self.page < response.data.pages) {
                        self.loadMoreBtn.show();
                    } else {
                        self.loadMoreBtn.hide();
                    }
                },
                error: function() {
                    self.showError('Error connecting to the server');
                },
                complete: function() {
                    self.loading = false;
                    self.grid.find('.cocktail-loading').hide();
                    self.loadMoreBtn.prop('disabled', false);
                }
            });
        },
        
        openCocktailModal: function(cocktailId) {
            const self = this;
            
            // Show loading state
            this.modalBody.html('<div class="cocktail-loading"><div class="spinner"></div><p>Loading cocktail details...</p></div>');
            this.modal.css('display', 'block');
            
            // Get cocktail data via AJAX
            $.ajax({
                url: liveFoundationData.rest_url + 'cocktails/' + cocktailId,
                type: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', liveFoundationData.nonce);
                },
                success: function(data) {
                    self.renderCocktailModal(data);
                },
                error: function() {
                    self.modalBody.html('<div class="error-message">Failed to load cocktail details</div>');
                }
            });
        },
        
        renderCocktailModal: function(cocktail) {
            const acf = cocktail.acf || {};
            const ingredients = acf.ingredients || [];
            const recipeSteps = acf.recipe_steps || [];
            
            let html = `
                <div class="cocktail-modal-header">
                    <h2>${cocktail.title.rendered}</h2>
                </div>
                
                <div class="cocktail-modal-body">
                    <div class="cocktail-modal-image-wrapper">
                        ${cocktail.featured_media ? 
                            `<img src="${cocktail._embedded['wp:featuredmedia'][0].source_url}" alt="${cocktail.title.rendered}" class="cocktail-modal-image">` : 
                            '<div class="no-image"></div>'}
                    </div>
                    
                    <div class="cocktail-modal-details">
                        <div class="cocktail-modal-meta">`;
            
            // Add difficulty if available
            if (cocktail.meta && cocktail.meta._cocktail_difficulty) {
                html += `
                    <div class="cocktail-meta-item">
                        <span class="meta-label">Difficulty:</span>
                        <span class="meta-value">${cocktail.meta._cocktail_difficulty[0]}</span>
                    </div>`;
            }
            
            // Add prep time if available
            if (cocktail.meta && cocktail.meta._cocktail_prep_time) {
                html += `
                    <div class="cocktail-meta-item">
                        <span class="meta-label">Preparation Time:</span>
                        <span class="meta-value">${cocktail.meta._cocktail_prep_time[0]} minutes</span>
                    </div>`;
            }
            
            // Add glass type if available
            if (cocktail.meta && cocktail.meta._cocktail_glass_type) {
                html += `
                    <div class="cocktail-meta-item">
                        <span class="meta-label">Glass Type:</span>
                        <span class="meta-value">${cocktail.meta._cocktail_glass_type[0]}</span>
                    </div>`;
            }
            
            html += `</div>`;
            
            // Add ingredients section
            if (ingredients.length > 0) {
                html += `
                    <div class="cocktail-ingredients">
                        <h3>Ingredients</h3>
                        <ul>`;
                
                ingredients.forEach(function(ingredient) {
                    html += `
                        <li>
                            <span class="ingredient-amount">${ingredient.amount} ${ingredient.unit}</span>
                            <span class="ingredient-name">${ingredient.name}</span>
                        </li>`;
                });
                
                html += `
                        </ul>
                    </div>`;
            }
            
            // Add recipe steps section
            if (recipeSteps.length > 0) {
                html += `
                    <div class="cocktail-recipe">
                        <h3>Instructions</h3>
                        <ol class="recipe-steps">`;
                
                recipeSteps.forEach(function(item, index) {
                    html += `<li>${item.step}</li>`;
                });
                
                html += `
                        </ol>
                    </div>`;
            } else {
                // If no recipe steps, use post content
                html += `
                    <div class="cocktail-description">
                        <h3>Instructions</h3>
                        ${cocktail.content.rendered}
                    </div>`;
            }
            
            // Add categories if available
            if (cocktail._embedded && cocktail._embedded['wp:term'] && cocktail._embedded['wp:term'][0]) {
                const categories = cocktail._embedded['wp:term'][0];
                
                if (categories.length > 0) {
                    html += `
                        <div class="cocktail-categories">
                            <p class="category-label">Categories:</p>
                            <div class="category-tags">`;
                    
                    categories.forEach(function(category) {
                        html += `<span class="category-tag">${category.name}</span>`;
                    });
                    
                    html += `
                            </div>
                        </div>`;
                }
            }
            
            html += `
                    </div>
                </div>
            `;
            
            this.modalBody.html(html);
        },
        
        closeModal: function() {
            this.modal.css('display', 'none');
            this.modalBody.empty();
        },
        
        showError: function(message) {
            this.grid.html(`<div class="error-message">${message}</div>`);
            this.grid.find('.cocktail-loading').hide();
        }
    };

    $(document).ready(function() {
        CocktailsList.init();
    });

})(jQuery);