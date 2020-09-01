import animateScrollTo from 'animated-scroll-to';

window.WPUPG_Pagination_pages = {
    init: ( gridElemId, args ) => {
        const id = `${ gridElemId }-pagination`;
        const elem = document.querySelector( '#' + id );

        let pagination = {
            gridElemId,
            args,
            id,
            elem,
            page: 0,
            pagesLoaded: [0],
            getDeeplink() {
                return this.page ? `p:${ this.page }` : '';
            },
            restoreDeeplink( key, value ) {
                if ( 'p' === key ) {
                    const button = this.elem.querySelector( `.wpupg-pagination-term.wpupg-page-${ value }` );

                    if ( button ) {
                        return new Promise((resolve) => {
                            this.onClickButton( button, () => {
                                resolve();
                            } );
                        });
                    }
                }
            },
            getSelector() {
                return `.wpupg-page-${ this.page }`;
            },
            onClickButton( button, callback = false ) {
                const wasActive = button.classList.contains( 'active' );

                if ( ! wasActive ) {
                    // Deactivate other buttons.
                    for ( let otherButton of this.buttons ) {
                        otherButton.classList.remove( 'active' );
                    }

                    // Set current button active.
                    button.classList.add( 'active' );

                    // Scroll to top of grid if not in view.
                    const gridElem = WPUPG_Grids[ this.gridElemId ].elem;
                    const bounding = gridElem.getBoundingClientRect();

                    if ( bounding.top < 0 ) {
                        animateScrollTo( gridElem, {
                            verticalOffset: -100,
                            speed: 500,
                        } );
                    }

                    // Load page.
                    this.changePage( button, ( page ) => {
                        this.page = page;

                        // Trigger grid filter.
                        WPUPG_Grids[ pagination.gridElemId ].filter();

                        // Optional callback.
                        if ( false !== callback ) {
                            callback( page );
                        }
                    });
                }
            },
            changePage( button, callback = false ) {
                const page = parseInt( button.dataset.page );

                if ( this.pagesLoaded.includes( page ) ) {
                    callback( page );
                } else {
                    // Set Loading state for button.
                    const buttonStyle = window.getComputedStyle( button );
                    const backgroundColor = buttonStyle.getPropertyValue( 'background-color' );

                    button.style.color = backgroundColor;
                    button.classList.add( 'wpupg-spinner' );

                    // Load next page.
                    WPUPG_Grids[ pagination.gridElemId ].loadItems({
                        page,
                    }, () => {
                        button.classList.remove( 'wpupg-spinner' );
                        button.style.color = '';

                        this.pagesLoaded.push( page );

                        if ( false !== callback ) {
                            callback( page );
                        }
                    })
                }
            },
        }

        if ( elem ) {
            pagination.buttons = elem.querySelectorAll( '.wpupg-pagination-term' );

            // Add event listeners.
            for ( let button of pagination.buttons ) {
                button.addEventListener( 'click', (e) => {
                    if ( e.which === 1 ) { // Left mouse click.
                        pagination.onClickButton( button );
                    }
                } );
                button.addEventListener( 'keydown', (e) => {
                    if ( e.which === 13 || e.which === 32 ) { // Space or ENTER.
                        pagination.onClickButton( button );
                    }
                } );
            }
        }

        return pagination;
    },
}