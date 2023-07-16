<script>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import axios from 'axios';
import {defineComponent} from "vue";
import Company from "@/Pages/Company.vue";
import {Head} from "@inertiajs/vue3";

export default defineComponent({
    components: {
        Head,
        Company,
        AuthenticatedLayout,
    },
    data() {
        return {
            searchQuery: 'AAPL',
            searchResults: [],
            active: null,
            transactions: this.$page.props.userTransactions,
            portfolio: this.$page.props.userPortfolio,
            watchlist: this.$page.props.userWatchlist,
            wallet: this.$page.props.userWallet,
            stocksData: {}
        }
    },
    methods: {
        async searchStocks() {
            if (this.searchQuery && this.searchQuery.length > 0) {
                try {
                    axios.defaults.headers.common = {};
                    const response = await axios.get(`/api/search/${this.searchQuery}`)
                        .then(response => {
                            this.searchResults = response.data;
                            console.log(this.searchResults);
                        })
                        .catch(error => {
                            console.error('Error searching stocks:', error);
                        });

                } catch (error) {
                    console.error('Error searching stocks:', error);
                }
            } else {
                this.searchResults = [];
            }
        },
        getBulkSnapshots() {
            const tickers = this.consolidateWatchlistStockIds();
            axios.post('/api/stocks/bulk-snapshots', { tickers })
                .then(response => {
                    this.stocksData = response.data;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        async activate(ticker) {
            await this.searchStocks();
            const selectedStock = this.searchResults.find(stock => stock.ticker === ticker);
            this.active = selectedStock;
        },
        consolidateStockIds() {
            // Concatenate the portfolio and watchlist arrays
            let allStocks = this.portfolio.concat(this.watchlist);

            // Create a new array containing only unique stock_ids
            let uniqueStockIds = [...new Set(allStocks.map(item => item.stock_id))];

            return uniqueStockIds;
        },
        consolidateWatchlistStockIds() {
            // Concatenate the portfolio and watchlist arrays
            let allStocks = this.watchlist;

            // Create a new array containing only unique stock_ids
            let uniqueStockIds = [...new Set(allStocks.map(item => item.stock_id))];

            return uniqueStockIds;
        },
        formattedDate(newDate) {
            let date = new Date(newDate);
            let day = String(date.getDate()).padStart(2, '0');
            let month = String(date.getMonth() + 1).padStart(2, '0'); // January is 0!
            let year = date.getFullYear();

            return month + '/' + day + '/' + year.toString().substr(-2);
        },
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
            }).format(amount);
        },
        formatPercentage(value) {
            // multiply by 100 and fix to 2 decimal places
            return parseFloat(value).toFixed(2) + '%';
        }
    },
    computed: {
        consolidatePortfolio() {
            return this.portfolio.reduce((acc, item) => {
                // check if this stock_id is already in the accumulator array
                let found = acc.find(x => x.stock_id === item.stock_id);
                if (found) {
                    // if it is, increment the quantity
                    found.quantity += item.quantity;
                    // and calculate the total original price and current price
                    found.original_price = item.original_price;
                    found.current_price += item.current_price * item.quantity;
                } else {
                    // if it's not, calculate the total original price and current price
                    // and then push it into the accumulator array
                    item.original_price = item.original_price;
                    item.current_price = item.current_price * item.quantity;
                    acc.push(item);
                }
                return acc;
            }, []);
        },
        portfolio_watchlist_stocks() {
            return this.consolidateWatchlistStockIds();
        },
        portfolioValue() {
            // First, calculate the total value of all stocks in the portfolio
            let stockValue = this.mappedStockData.reduce((total, stock) => {
                return total + (stock.prevDay * stock.quantity);
            }, 0);

            // Then add this value to the current wallet balance
            return stockValue;
        },
        portfolioVolume() {
            let stockVolume = this.mappedStockData.reduce((total, stock) => {
                return total + stock.quantity;
            }, 0);

            // Then add this value to the current wallet balance
            return stockVolume;
        },
        mappedStockData() {
            const portfolioWithStockData = this.portfolio_watchlist_stocks.map(item => {
                const stockData = this.stocksData[item];
                if (stockData && stockData.ticker) {
                    return {
                        item,
                        todaysChange: stockData.ticker.todaysChange,
                        todaysChangePerc: stockData.ticker.todaysChangePerc,
                        updated: stockData.ticker.updated,
                        day: stockData.ticker.day,
                        min: stockData.ticker.min,
                        prevDay: stockData.ticker.prevDay.c,
                        status: stockData.status,
                        request_id: stockData.request_id
                    };
                }
                return item;
            });

            return portfolioWithStockData;
        },
    },
    mounted() {
        this.getBulkSnapshots();
    }
});

</script>

<template>
    <Head title="Watchlist" />

    <AuthenticatedLayout>

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Watchlist</h2>
        </template>

        <div v-if="active === null" class="p-8 mx-auto max-w-screen-xl items-center flex">
            <div class="relative flex flex-col w-full min-w-0 mb-0 break-words bg-white border-0 border-transparent border-solid shadow-md rounded-lg bg-clip-border">
                <div class="p-6 pb-0 mb-0 bg-white rounded-t-2xl">
                    <h6>&nbsp;</h6>
                </div>
                <div class="flex-auto px- pt-0 pb-2">
                    <div class="p-0 overflow-x-auto">
                        <table class="items-center w-full mb-0 align-top border-gray-200 text-slate-500">
                            <thead class="align-bottom">
                            <tr>
                                <th class="px-6 py-3 font-bold uppercase align-middle bg-transparent border-b border-gray-200 shadow-none text-xxs border-b-solid tracking-none whitespace-nowrap">Stock Ticker</th>
                                <th class="px-6 py-3 font-bold uppercase align-middle bg-transparent border-b border-gray-200 shadow-none text-xxs border-b-solid tracking-none whitespace-nowrap">Current Price</th>
                                <th class="px-6 py-3 font-bold uppercase align-middle bg-transparent border-b border-gray-200 shadow-none text-xxs border-b-solid tracking-none whitespace-nowrap">Daily Change %</th>
                                <th class="px-6 py-3 font-semibold capitalize align-middle bg-transparent border-b border-gray-200 border-solid shadow-none tracking-none whitespace-nowrap"></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="t in mappedStockData">
                                <td class="p-2 leading-normal text-center align-middle bg-transparent border-b text-sm whitespace-nowrap shadow-transparent">
                                    <div class="px-2 py-1">
                                        <div class="text-center">
                                            <h6 class="mb-0 leading-normal text-gray-800 text-sm">{{ t.item }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-2 text-center align-middle bg-transparent border-b whitespace-nowrap shadow-transparent">
                                    <span class="font-semibold leading-tight text-xs text-gray-800">{{ formatCurrency(t.prevDay) }}</span>
                                </td>
                                <td class="p-2 leading-normal text-center align-middle bg-transparent border-b text-sm whitespace-nowrap shadow-transparent">
                                    <p class="mb-0 font-semibold leading-tight text-xs text-gray-800">{{ formatPercentage(t.todaysChangePerc) }}</p>
                                </td>
                                <td class="p-2 align-middle bg-transparent border-b whitespace-nowrap shadow-transparent">
                                    <button @click="activate('AAPL')" type="button" class="inline-block px-6 py-3 font-bold text-center text-white uppercase align-middle transition-all rounded-lg cursor-pointer bg-gradient-to-tl from-blue-500 to-violet-500 leading-normal text-xs ease-in tracking-tight-rem shadow-xs bg-150 bg-x-25 hover:-translate-y-px active:opacity-85 hover:shadow-md">
                                        View
                                    </button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <nav class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-3 md:space-y-0 p-4" aria-label="Table navigation">
                    </nav>
                </div>
            </div>
        </div>

        <Company v-if="active !== null" :wallet="this.$page.props.userWallet" :ticker="active" @close="active = null" />
    </AuthenticatedLayout>
</template>
