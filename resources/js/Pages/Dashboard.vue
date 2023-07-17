<script>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import {defineComponent} from "vue";
import Company from "@/Pages/Company.vue";
import axios from "axios";

export default defineComponent({
    components: {
        Head,
        Company,
        AuthenticatedLayout,
    },
    data() {
        return {
            searchQuery: 'AAPL',
            transactions: this.$page.props.userTransactions,
            portfolio: this.$page.props.userPortfolio,
            watchlist: this.$page.props.userWatchlist,
            wallet: this.$page.props.userWallet,
            searchResults: [],
            active: null,
            stocksData: {},
            loading: true
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
                            console.log("Search: " + this.searchResults);
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
            const tickers = this.consolidateStockIds();
            axios.post('/api/stocks/bulk-snapshots', { tickers })
                .then(response => {
                    this.stocksData = response.data;
                    this.loading = false;
                })
                .catch(error => {
                    console.error(error);
                });
        },
        formattedDate(newDate) {
            let date = new Date(newDate);
            let day = String(date.getDate()).padStart(2, '0');
            let month = String(date.getMonth() + 1).padStart(2, '0'); // January is 0!
            let year = date.getFullYear();

            return month + '/' + day + '/' + year.toString().substr(-2);
        },
        consolidateStockIds() {
            // Allows us to get all relevant data from Polygon API for portfolio and watchlist stocks
            // Concatenate the portfolio and watchlist arrays
            let allStocks = this.portfolio.concat(this.watchlist)

            // Create a new array containing only unique stock_ids
            let uniqueStockIds = [...new Set(allStocks.map(item => item.stock_id))];

            return uniqueStockIds;
        },
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
            }).format(amount);
        },
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
            return this.consolidateStockIds();
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
            const portfolioWithStockData = this.consolidatePortfolio.map(item => {
                const stockData = this.stocksData[item.stock_id];
                if (stockData && stockData.ticker) {
                    return {
                        ...item,
                        todaysChange: stockData.ticker.todaysChange,
                        todaysChangePerc: stockData.ticker.todaysChangePerc,
                        updated: stockData.ticker.updated,
                        day: stockData.ticker.day,
                        min: stockData.ticker.min,
                        prevDay: stockData.ticker.prevDay.c,
                        diff: stockData.ticker.prevDay.c - item.original_price,
                        total: stockData.ticker.prevDay.c * item.quantity,
                        status: stockData.status,
                        request_id: stockData.request_id
                    };
                }
                return item;
            });

            return portfolioWithStockData;
        },
        mappedWatchlistData() {
            const portfolioWithStockData = this.watchlist.map(item => {
                const stockData = this.stocksData[item.stock_id];
                if (stockData && stockData.ticker) {
                    return {
                        ...item,
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
        }
    },
    mounted() {
        this.getBulkSnapshots();
    }
});

</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Test</h2>
        </template>

        <div class="p-8 w-full items-center flex">
            <div v-if="!loading" class="w-full sm:w-1/3 relative flex flex-col min-w-0 break-words bg-white shadow-md dark:bg-slate-850 dark:shadow-dark-xl rounded-2xl bg-clip-border">
                <div class="flex-auto p-4">
                    <div class="flex flex-row -mx-3">
                        <div class="flex-none w-2/3 max-w-full px-3">
                            <div>
                                <p class="mb-0 font-sans font-semibold leading-normal uppercase text-sm">Portfolio</p>
                                <p class="mb-0 dark:opacity-60">Total Value</p>
                                <h5 class="mb-2 font-bold">{{ formatCurrency(portfolioValue) }}</h5>
                                <p class="mb-0 dark:opacity-60">
                                    <span class="font-bold leading-normal text-sm text-emerald-500">+64.25 +0.45%</span>
                                </p>
                                <p class="mb-0 dark:opacity-60">Volume: {{ portfolioVolume }}</p>
                            </div>
                        </div>
                        <div class="px-3 text-right basis-1/3">
                            <div class="inline-block w-12 h-12 text-center rounded-circle bg-gradient-to-tl from-blue-500 to-violet-500">
                                <i class="fa fa-dollar-sign text-lg relative top-3.5 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="w-full sm:w-1/3 relative flex flex-col min-w-0 break-words bg-white shadow-md dark:bg-slate-850 dark:shadow-dark-xl rounded-2xl bg-clip-border">
                <div class="flex-auto p-4">
                    <div class="flex flex-row -mx-3">
                        <div class="flex-none w-2/3 max-w-full px-3">
                            <div>
                                <p class="mb-0 font-sans font-semibold leading-normal uppercase text-sm">Portfolio</p>
                                <p class="mb-0 dark:opacity-60">Total Value</p>
                                <h5 class="mb-2 font-bold">&nbsp;</h5>
                                <p class="mb-0 dark:opacity-60">
                                    <span class="font-bold leading-normal text-sm text-emerald-500">+64.25 +0.45%</span>
                                </p>
                                <p class="mb-0 dark:opacity-60">Volume: &nbsp;</p>
                            </div>
                        </div>
                        <div class="px-3 text-right basis-1/3">
                            <div class="inline-block w-12 h-12 text-center rounded-circle bg-gradient-to-tl from-blue-500 to-violet-500">
                                <i class="fa fa-dollar-sign text-lg relative top-3.5 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!loading" class="w-full ml-3 sm:w-1/3 relative flex flex-col min-w-0 break-words bg-white shadow-md dark:bg-slate-850 dark:shadow-dark-xl rounded-2xl bg-clip-border">
                <div class="flex-auto p-4">
                    <div class="flex flex-row -mx-3">
                        <div class="flex-none w-2/3 max-w-full px-3">
                            <div>
                                <p class="mb-0 font-sans font-semibold leading-normal uppercase text-sm">Wallet</p>
                                <p class="mb-0 dark:opacity-60">Balance</p>
                                <h5 class="mb-2 font-bold">{{ formatCurrency(this.wallet.balance) }}</h5>
                                <p class="mb-0 dark:opacity-60">
                                    <span class="font-bold leading-normal text-sm text-emerald-500">+64.25 +0.45%</span>
                                </p>
                                <p class="mb-0 dark:opacity-60">&nbsp;</p>
                            </div>
                        </div>
                        <div class="px-3 text-right basis-1/3">
                            <div class="inline-block w-12 h-12 text-center rounded-circle bg-gradient-to-tl from-blue-500 to-violet-500">
                                <i class="fa fa-coins text-lg relative top-3.5 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="w-full ml-3 sm:w-1/3 relative flex flex-col min-w-0 break-words bg-white shadow-md dark:bg-slate-850 dark:shadow-dark-xl rounded-2xl bg-clip-border">
                <div class="flex-auto p-4">
                    <div class="flex flex-row -mx-3">
                        <div class="flex-none w-2/3 max-w-full px-3">
                            <div>
                                <p class="mb-0 font-sans font-semibold leading-normal uppercase text-sm">Wallet</p>
                                <p class="mb-0 dark:opacity-60">Balance</p>
                                <h5 class="mb-2 font-bold">&nbsp;</h5>
                                <p class="mb-0 dark:opacity-60">
                                    <span class="font-bold leading-normal text-sm text-emerald-500">+64.25 +0.45%</span>
                                </p>
                                <p class="mb-0 dark:opacity-60">&nbsp;</p>
                            </div>
                        </div>
                        <div class="px-3 text-right basis-1/3">
                            <div class="inline-block w-12 h-12 text-center rounded-circle bg-gradient-to-tl from-blue-500 to-violet-500">
                                <i class="fa fa-coins text-lg relative top-3.5 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!loading" class="w-full ml-5 sm:w-1/3 relative flex flex-col min-w-0 break-words bg-white shadow-md dark:bg-slate-850 dark:shadow-dark-xl rounded-2xl bg-clip-border">
                <div class="flex-auto p-4">
                    <div class="flex flex-row -mx-3">
                        <div class="flex-none w-2/3 max-w-full px-3">
                            <div>
                                <p class="mb-0 font-sans font-semibold leading-normal uppercase text-sm">COMP</p>
                                <p class="mb-0 dark:opacity-60">NASDAQ Composite Index</p>
                                <h5 class="mb-2 font-bold">14,202.82</h5>
                                <p class="mb-0 dark:opacity-60">
                                    <span class="font-bold leading-normal text-sm text-emerald-500">+64.25 +0.45%</span>
                                </p>
                                <p class="mb-0 dark:opacity-60">Volume: 753,565,851</p>
                            </div>
                        </div>
                        <div class="px-3 text-right basis-1/3">
                            <div class="inline-block w-12 h-12 text-center rounded-circle bg-gradient-to-tl from-blue-500 to-violet-500">
                                <i class="fa fa-arrow-trend-up text-lg relative top-3.5 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="w-full ml-5 sm:w-1/3 relative flex flex-col min-w-0 break-words bg-white shadow-md dark:bg-slate-850 dark:shadow-dark-xl rounded-2xl bg-clip-border">
                <div class="flex-auto p-4">
                    <div class="flex flex-row -mx-3">
                        <div class="flex-none w-2/3 max-w-full px-3">
                            <div>
                                <p class="mb-0 font-sans font-semibold leading-normal uppercase text-sm">COMP</p>
                                <p class="mb-0 dark:opacity-60">NASDAQ Composite Index</p>
                                <h5 class="mb-2 font-bold">14,202.82</h5>
                                <p class="mb-0 dark:opacity-60">
                                    <span class="font-bold leading-normal text-sm text-emerald-500">+64.25 +0.45%</span>
                                </p>
                                <p class="mb-0 dark:opacity-60">Volume: 753,565,851</p>
                            </div>
                        </div>
                        <div class="px-3 text-right basis-1/3">
                            <div class="inline-block w-12 h-12 text-center rounded-circle bg-gradient-to-tl from-blue-500 to-violet-500">
                                <i class="fa fa-arrow-trend-up text-lg relative top-3.5 text-white"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="pr-8 pb-8 pl-8 flex">

            <div class="w-full sm:w-1/3 bg-white border rounded-lg shadow lg:p-8">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="text-xl font-bold leading-none text-gray-800">Portfolio</h5>
                    <a :href="route('portfolio')" class="text-sm font-medium text-blue-600 hover:underline">
                        View all
                    </a>
                </div>
                <div class="bg-white overflow-hidden sm:rounded-lg">
                    <ul v-if="!loading" role="list" class="divide-y divide-gray-100">
                        <li v-for="p in mappedStockData.slice(0, 6)" :key="p.stock_id" class="py-3 sm:py-4">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ p.stock_id }}
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        {{ formatCurrency(p.prevDay) ?? 'N/A' }}
                                    </p>
                                </div>
                                <div class="inline-flex items-center text-base font-semibold text-gray-900">
                                    {{ formatCurrency(p.total) }}
                                </div>
                            </div>
                        </li>
                    </ul>
                    <ul v-else role="list" class="divide-y divide-gray-100">
                        <li class="py-3 sm:py-4">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        &nbsp;
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        &nbsp;
                                    </p>
                                </div>
                                <div class="flex-2 pr-0 min-w-0 font-semibold text-gray-900">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        &nbsp;
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        &nbsp;
                                    </p>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        &nbsp;
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        &nbsp;
                                    </p>
                                </div>
                                <div class="flex-2 pr-0 min-w-0 font-semibold text-gray-900">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        &nbsp;
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        &nbsp;
                                    </p>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        &nbsp;
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        &nbsp;
                                    </p>
                                </div>
                                <div class="flex-2 pr-0 min-w-0 font-semibold text-gray-900">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        &nbsp;
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        &nbsp;
                                    </p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="w-full ml-5 sm:w-1/3 bg-white border rounded-lg shadow lg:p-8">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="text-xl font-bold leading-none text-gray-800">Watchlist</h5>
                    <a :href="route('watchlist')" class="text-sm font-medium text-blue-600 hover:underline">
                        View all
                    </a>
                </div>
                <div class="bg-white overflow-hidden sm:rounded-lg">
                    <ul v-if="!loading" role="list" class="divide-y divide-gray-100">
                        <li v-for="p in mappedWatchlistData.slice(0, 6)" :key="p.stock_id" class="py-3 sm:py-4">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ p.stock_id }}
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        {{ formatCurrency(p.todaysChange) ?? 'N/A' }}
                                    </p>
                                </div>
                                <div class="inline-flex items-center text-base font-semibold text-gray-900">
                                    {{ formatCurrency(p.prevDay) ?? 'N/A' }}
                                </div>
                            </div>
                        </li>
                    </ul>
                    <ul v-else role="list" class="divide-y divide-gray-100">
                        <li class="py-3 sm:py-4">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        &nbsp;
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        &nbsp;
                                    </p>
                                </div>
                                <div class="flex-2 pr-0 min-w-0 font-semibold text-gray-900">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        &nbsp;
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        &nbsp;
                                    </p>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        &nbsp;
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        &nbsp;
                                    </p>
                                </div>
                                <div class="flex-2 pr-0 min-w-0 font-semibold text-gray-900">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        &nbsp;
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        &nbsp;
                                    </p>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        &nbsp;
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        &nbsp;
                                    </p>
                                </div>
                                <div class="flex-2 pr-0 min-w-0 font-semibold text-gray-900">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        &nbsp;
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        &nbsp;
                                    </p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="w-full ml-5 sm:w-1/3 bg-white border rounded-lg shadow lg:p-8">
                <div class="flex items-center justify-between mb-4">
                    <h5 class="text-xl font-bold leading-none text-gray-800">Recent Transactions</h5>
                    <a :href="route('transactions')" class="text-sm font-medium text-blue-600 hover:underline">
                        View all
                    </a>
                </div>
                <div class="bg-white overflow-hidden sm:rounded-lg">
                    <ul v-if="!loading" role="list" class="divide-y divide-gray-100">
                        <li v-for="p in transactions.slice(0, 6)" :key="p.stock_id" class="py-3 sm:py-4">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ p.stock_id }}
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        {{ p.quantity }}
                                    </p>
                                </div>
                                <div class="flex-2 pr-0 min-w-0 font-semibold text-gray-900">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ formatCurrency(p.total_price) }}
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        {{ p.price_per_stock }}
                                    </p>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <ul v-else role="list" class="divide-y divide-gray-100">
                        <li class="py-3 sm:py-4">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        &nbsp;
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        &nbsp;
                                    </p>
                                </div>
                                <div class="flex-2 pr-0 min-w-0 font-semibold text-gray-900">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        &nbsp;
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        &nbsp;
                                    </p>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        &nbsp;
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        &nbsp;
                                    </p>
                                </div>
                                <div class="flex-2 pr-0 min-w-0 font-semibold text-gray-900">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        &nbsp;
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        &nbsp;
                                    </p>
                                </div>
                            </div>
                        </li>
                        <li>
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        &nbsp;
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        &nbsp;
                                    </p>
                                </div>
                                <div class="flex-2 pr-0 min-w-0 font-semibold text-gray-900">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        &nbsp;
                                    </p>
                                    <p class="text-sm text-gray-500 truncate ">
                                        &nbsp;
                                    </p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
