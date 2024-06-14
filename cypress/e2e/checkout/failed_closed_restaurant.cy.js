describe('Failed checkout; restaurant is closed', () => {
  [ 'logged in customer', 'guest checkout' ].forEach((customerType) => {

    describe(` (${ customerType })`, () => {

      beforeEach(() => {

        cy.symfonyConsole(
          'coopcycle:fixtures:load -f cypress/fixtures/checkout.yml')

        cy.window().then((win) => {
          win.sessionStorage.clear()
        })

        switch (customerType) {
          case 'logged in customer': {
            cy.visit('/login')
            cy.login('bob', '12345678')
            break
          }
          case 'guest checkout': {
            cy.symfonyConsole(
              'craue:setting:create --section="general" --name="guest_checkout_enabled" --value="1" --force')
            break
          }
        }
      })

      context('restaurant is closed while the customer is on the menu page' +
        ` (${ customerType })`, () => {
        it('suggest to choose a new time range (Timing modal)' + ` (${ customerType })`,
          () => {

            cy.visit('/fr/')

            cy.intercept('POST', '/fr/restaurant/*/cart')
              .as('postRestaurantCart1')

            cy.clickRestaurant(
              'Crazy Hamburger',
              /\/fr\/restaurant\/[0-9]+-crazy-hamburger/,
            )

            //FIXME: why do we send two requests?
            cy.wait([ '@postRestaurantCart1', '@postRestaurantCart1' ])

            cy.intercept('POST', '/fr/restaurant/*/cart/product/*')
              .as('postProduct1')

            cy.addProduct('Cheeseburger', '#CHEESEBURGER-options', 2, [
              'HAMBURGER_ACCOMPANIMENT_FRENCH_FRIES',
              'HAMBURGER_DRINK_COLA' ])

            cy.wait('@postProduct1', { timeout: 5000 })

            cy.get('.cart__items')
              .invoke('text')
              .should('match', /Cheeseburger/)

            cy.intercept('POST', '/fr/restaurant/*/cart')
              .as('postRestaurantCart2')

            cy.searchAddress(
              '.ReactModal__Content--enter-address',
              '91 rue de rivoli paris',
              /^91,? Rue de Rivoli,? 75001,? Paris,? France/i,
            )

            cy.wait('@postRestaurantCart2')

            cy.get(
              '#restaurant__fulfilment-details__container [data-testid="cart.shippingAddress"]')
              .invoke('text')
              .should('match', /^91,? Rue de Rivoli,? 75001,? Paris,? France/i)

            cy.closeRestaurantForToday('resto_1', 'resto_1')

            cy.get('.order-button:visible').click()

            cy.get('[data-testid="order.timeRangeChangedModal"]')
              .should('be.visible')

            cy.intercept('POST', '/fr/restaurant/*/cart')
              .as('postRestaurantCart3')
            cy.get(
              '[data-testid="order.timeRangeChangedModal.setTimeRange"]:visible button')
              .click()
            cy.wait('@postRestaurantCart3')

            cy.get(
              '[data-testid="cart.time"]:visible')
              .invoke('text')
              .should('match', /^Demain entre 10:00 et 10:10/i)

            cy.get('.order-button:visible').click()

            cy.location('pathname').should('eq', '/order/')
          })
      })

      context('restaurant is closed while the customer is on the address page' +
        ` (${ customerType })`,
        () => {
          it('suggest to choose a new time range (Timing modal)' + ` (${ customerType })`,
            () => {

              cy.intercept('POST', '/fr/restaurant/*/cart')
                .as('postRestaurantCart1')
              cy.intercept('POST', '/fr/restaurant/*/cart/product/*')
                .as('postProduct')

              cy.visit('/fr/')

              cy.clickRestaurant(
                'Crazy Hamburger',
                /\/fr\/restaurant\/[0-9]+-crazy-hamburger/,
              )

              //FIXME: why do we send two requests?
              cy.wait([ '@postRestaurantCart1', '@postRestaurantCart1' ])

              cy.addProduct('Cheeseburger', '#CHEESEBURGER-options', 2, [
                'HAMBURGER_ACCOMPANIMENT_FRENCH_FRIES',
                'HAMBURGER_DRINK_COLA' ])

              cy.wait('@postProduct', { timeout: 5000 })

              cy.get('.cart__items')
                .invoke('text')
                .should('match', /Cheeseburger/)

              cy.intercept('POST', '/fr/restaurant/*/cart')
                .as('postRestaurantCart2')

              cy.searchAddress(
                '.ReactModal__Content--enter-address',
                '91 rue de rivoli paris',
                /^91,? Rue de Rivoli,? 75001,? Paris,? France/i,
              )

              cy.wait('@postRestaurantCart2')

              cy.get(
                '#restaurant__fulfilment-details__container [data-testid="cart.shippingAddress"]')
                .invoke('text')
                .should('match',
                  /^91,? Rue de Rivoli,? 75001,? Paris,? France/i)

              cy.get('.order-button:visible').click()

              cy.location('pathname').should('eq', '/order/')

              cy.get('input[name="checkout_address[customer][fullName]"]')
                .type('John Doe')

              if (customerType === 'guest checkout') {
                cy.get('input[name="checkout_address[customer][email]"]')
                  .type('test@gmail.com')

                cy.get('input[name="checkout_address[customer][phoneNumber]"]')
                  .type('+33612345678')

                cy.get('input[name="checkout_address[customer][legal]"]')
                  .check()
              }

              cy.closeRestaurantForToday('resto_1', 'resto_1')

              cy.contains('Commander').click()

              cy.get('[data-testid="order.timeRangeChangedModal"]')
                .should('be.visible')

              cy.intercept('PUT', '/api/orders/*')
                .as('putOrder1')
              cy.get(
                '[data-testid="order.timeRangeChangedModal.setTimeRange"]:visible button')
                .click()
              cy.wait('@putOrder1')

              cy.get(
                '[data-testid="order.time"]:visible')
                .invoke('text')
                .should('match', /^Demain entre 10:00 et 10:10/i)

              cy.get('input[name="checkout_address[customer][fullName]"]')
                .type('John Doe')

              if (customerType === 'guest checkout') {
                cy.get('input[name="checkout_address[customer][email]"]')
                  .type('test@gmail.com')

                cy.get('input[name="checkout_address[customer][phoneNumber]"]')
                  .type('+33612345678')

                cy.get('input[name="checkout_address[customer][legal]"]')
                  .check()
              }

              cy.contains('Commander').click()

              cy.location('pathname').should('eq', '/order/payment')
            })
        })

      context('restaurant is closed while the customer is on the payment page' +
        ` (${ customerType })`,
        () => {
          it('proceed with payment (FIXME)' + ` (${ customerType })`, () => {

            cy.visit('/fr/')

            cy.intercept('POST', '/fr/restaurant/*/cart')
              .as('postRestaurantCart1')

            cy.clickRestaurant(
              'Crazy Hamburger',
              /\/fr\/restaurant\/[0-9]+-crazy-hamburger/,
            )

            //FIXME: why do we send two requests?
            cy.wait([ '@postRestaurantCart1', '@postRestaurantCart1' ])

            cy.intercept('POST', '/fr/restaurant/*/cart/product/*')
              .as('postProduct1')

            cy.addProduct('Cheeseburger', '#CHEESEBURGER-options', 2, [
              'HAMBURGER_ACCOMPANIMENT_FRENCH_FRIES',
              'HAMBURGER_DRINK_COLA' ])

            cy.wait('@postProduct1', { timeout: 5000 })

            cy.get('.cart__items')
              .invoke('text')
              .should('match', /Cheeseburger/)

            cy.intercept('POST', '/fr/restaurant/*/cart')
              .as('postRestaurantCart2')

            cy.searchAddress(
              '.ReactModal__Content--enter-address',
              '91 rue de rivoli paris',
              /^91,? Rue de Rivoli,? 75001,? Paris,? France/i,
            )

            cy.wait('@postRestaurantCart2')

            cy.get(
              '#restaurant__fulfilment-details__container [data-testid="cart.shippingAddress"]')
              .invoke('text')
              .should('match', /^91,? Rue de Rivoli,? 75001,? Paris,? France/i)

            cy.get('.order-button:visible').click()

            cy.location('pathname').should('eq', '/order/')

            cy.get('input[name="checkout_address[customer][fullName]"]')
              .type('John Doe')

            if (customerType === 'guest checkout') {
              cy.get('input[name="checkout_address[customer][email]"]')
                .type('test@gmail.com')

              cy.get('input[name="checkout_address[customer][phoneNumber]"]')
                .type('+33612345678')

              cy.get('input[name="checkout_address[customer][legal]"]')
                .check()
            }

            cy.contains('Commander').click()

            cy.location('pathname').should('eq', '/order/payment')

            cy.get('form[name="checkout_payment"] input[type="text"]')
              .type('John Doe')
            cy.enterCreditCard()

            cy.closeRestaurantForToday('resto_1', 'resto_1')

            cy.get('form[name="checkout_payment"]').submit()

            //FIXME: this behaviour is broken since https://github.com/coopcycle/coopcycle-web/pull/3971
            // and it will be re-introduced in https://github.com/coopcycle/coopcycle-web/issues/4167
            cy.get('#order-timeline')
              .contains('Commande en attente de validation')

            // cy.get('form[name="checkout_address"]')
            //   .contains('Il n\'est plus possible de commander pour aujourd\'hui')
          })
        })
    })
  })
})
