class CartState {
    static #instance = null;
    #count = 0;
    #items = [];
    #observers = [];

    static getInstance() {
        if (!CartState.#instance) {
            CartState.#instance = new CartState();
        }
        return CartState.#instance;
    }

    async initialize() {
        await this.refreshItems();
        await this.refreshCount();
    }

    async refreshItems() {
        try {
            const response = await fetch('/nsikacart/api/products/saved-list/get-saved-items.php');
            const text = await response.text();
            
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    this.#items = data.saved_items.map(item => ({
                        productId: item.product_id.toString(),
                        quantity: item.quantity,
                        name: item.name,
                        dollar: parseFloat(item.dollar),
                        description: item.description,
                        image: item.image || '`/nsikacart/public/assets/placeholder.png`',
                        postedDate: item.posted_date,
                        location: item.location,
                        sellerName: item.seller_name
                    }));
                    this.notifyObservers();
                }
            } catch (parseError) {
                console.error('Invalid JSON response:', text);
            }
        } catch (error) {
            console.error('Error fetching saved items:', error);
        }
    }

    async refreshCount() {
        try {
            const response = await fetch('/nsikacart/api/products/saved-list/get-saved-count.php');
            const text = await response.text();
            
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    this.updateCount(data.count);
                } else {
                    console.error('Server error:', data.message);
                }
            } catch (parseError) {
                console.error('Invalid JSON response:', text);
                // Set count to 0 on error
                this.updateCount(0);
            }
        } catch (error) {
            console.error('Error fetching saved items count:', error);
            // Set count to 0 on error
            this.updateCount(0);
        }
    }

    updateCount(newCount) {
        this.#count = newCount;
        this.notifyObservers();
    }

    getCount() {
        return this.#count;
    }

    getItems() {
        return [...this.#items];
    }

    subscribe(callback) {
        this.#observers.push(callback);
        // Immediately call with current count
        callback(this.#count);
    }

    unsubscribe(callback) {
        this.#observers = this.#observers.filter(observer => observer !== callback);
    }

    notifyObservers() {
        this.#observers.forEach(callback => callback(this.#count));
    }
}

export const cartState = CartState.getInstance();