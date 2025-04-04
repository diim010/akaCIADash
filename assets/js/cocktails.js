(function($) {
    'use strict';

    const CocktailsList = {
        init: function() {
            this.container = $('.cocktails-container');
            this.grid = this.container.find('.cocktails-grid');
            this.loader = this.container.find('.cocktails-loader');
            this.pagination = this.container.find('.cocktails-pagination');
            this.searchInput = this.container.find('.cocktails-search');
            
            this.page = 1;
            this.searchTerm = '';
            
            this.bindEvents();
            this.loadCocktails();
        },

        bindEvents: function() {
            const self = this;

            // Поиск с debounce
            let searchTimeout;
            this.searchInput.on('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    self.searchTerm = $(this).val();
                    self.page = 1;
                    self.loadCocktails();
                }, 500);
            });

            // Пагинация
            this.pagination.on('click', 'a', function(e) {
                e.preventDefault();
                self.page = $(this).data('page');
                self.loadCocktails();
                $('html, body').animate({
                    scrollTop: self.container.offset().top - 50
                }, 500);
            });
        },

        loadCocktails: function() {
            const self = this;
            this.loader.show();
            this.grid.empty();

            $.ajax({
                url: liveCocktails.restUrl + '/cocktails',
                method: 'GET',
                data: {
                    page: this.page,
                    per_page: liveCocktails.perPage,
                    search: this.searchTerm
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', liveCocktails.nonce);
                },
                success: function(response, textStatus, xhr) {
                    self.renderCocktails(response);
                    self.renderPagination(xhr.getResponseHeader('X-WP-Total'), xhr.getResponseHeader('X-WP-TotalPages'));
                },
                error: function() {
                    self.grid.html(`<div class="cocktails-error">${liveCocktails.i18n.error}</div>`);
                    self.loader.hide();
                }
            });
        },

        renderCocktails: function(cocktails) {
            if (!cocktails.length) {
                this.grid.html(`<div class="cocktails-no-results">${liveCocktails.i18n.noResults}</div>`);
                this.loader.hide();
                return;
            }

            const html = cocktails.map(cocktail => {
                const meta = cocktail.meta || {};
                return `
                    <div class="cocktail-card">
                        ${meta.thumbnail ? `
                            <div class="cocktail-image">
                                <img src="${meta.thumbnail}" alt="${cocktail.title.rendered}">
                            </div>
                        ` : ''}
                        <h3>${cocktail.title.rendered}</h3>
                        
                        <div class="cocktail-ingredients">
                            <h4>${liveCocktails.i18n.ingredients}</h4>
                            <ul>
                                ${(meta.ingredients || []).map(ingredient => 
                                    `<li>${ingredient}</li>`
                                ).join('')}
                            </ul>
                        </div>

                        <div class="cocktail-instructions">
                            <h4>${liveCocktails.i18n.instructions}</h4>
                            <p>${meta.instructions || ''}</p>
                        </div>
                    </div>
                `;
            }).join('');

            this.grid.html(html);
            this.loader.hide();
        },

        renderPagination: function(total, totalPages) {
            if (totalPages <= 1) {
                this.pagination.empty();
                return;
            }

            let html = '<div class="cocktails-pagination-links">';
            
            for (let i = 1; i <= totalPages; i++) {
                html += `<a href="#" class="page-numbers${i === this.page ? ' current' : ''}" data-page="${i}">${i}</a>`;
            }
            
            html += '</div>';
            this.pagination.html(html);
        }
    };

    $(document).ready(function() {
        CocktailsList.init();
    });

})(jQuery);