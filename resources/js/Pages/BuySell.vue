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
            searchQuery: '',
            searchResults: [],
            active: null,
            transactions: this.$page.props.userTransactions,
            portfolio: this.$page.props.userPortfolio,
            watchlist: this.$page.props.userWatchlist,
            wallet: this.$page.props.userWallet,
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
        async activate(ticker) {
            const selectedStock = this.searchResults.find(stock => stock.ticker === ticker);
            this.active = selectedStock;
        }
    }
});

</script>

<template>
    <Head title="Buy & Sell" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Buy/Sell</h2>
        </template>

        <section v-if="active === null" class="p-3 sm:p-5">
            <div class="mx-auto max-w-screen-xl px-4 lg:px-12">
                <!-- Start coding here -->
                <div class="bg-white bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden">
                    <div class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 p-4">
                        <div class="w-full md:w-1/2">
                            <form @submit.prevent="searchStocks" class="flex items-center">
                                <label for="simple-search" class="sr-only">Search</label>
                                <div class="relative w-full">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <svg aria-hidden="true" class="w-5 h-5 text-gray-500" fill="currentColor" viewbox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <input v-model="searchQuery" type="text" id="simple-search" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2" placeholder="Search" required="">
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3">Company name</th>
                                <th scope="col" class="px-4 py-3">Symbol</th>
                                <th scope="col" class="px-4 py-3">Brand</th>
                                <th scope="col" class="px-4 py-3">Description</th>
                                <th scope="col" class="px-4 py-3">Price</th>
                                <th scope="col" class="px-4 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                            </thead>
                            <tbody v-if="searchResults.length">
                                <tr v-for="stock in searchResults" :key="stock.ticker" class="border-b">
                                    <th scope="row" class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">{{ stock.name }}</th>
                                    <td class="px-4 py-3">{{ stock.ticker }}</td>
                                    <td class="px-4 py-3">{{ stock.market }}</td>
                                    <td class="px-4 py-3">{{ stock.primary_exchange }}</td>
                                    <td class="px-4 py-3">{{ stock.market_cap }}</td>
                                    <td class="px-4 py-3 flex items-center justify-center">
                                        <button @click="activate(stock.ticker)" type="button" class="inline-block px-6 py-3 font-bold text-center text-white uppercase align-middle transition-all rounded-lg cursor-pointer bg-gradient-to-tl from-blue-500 to-violet-500 leading-normal text-xs ease-in tracking-tight-rem shadow-xs bg-150 bg-x-25 hover:-translate-y-px active:opacity-85 hover:shadow-md">
                                            View
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <th scope="row" class="px-4 py-3 justify-center font-medium text-gray-900 whitespace-nowrap">--</th>
                                <td class="px-4 py-3">--</td>
                                <td class="px-4 py-3">--</td>
                                <td class="px-4 py-3">--</td>
                                <td class="px-4 py-3">--</td>
                                <td class="px-4 py-3">--</td>
                            </tbody>
                        </table>
                    </div>
                    <nav class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-3 md:space-y-0 p-4" aria-label="Table navigation">
                    </nav>
                </div>
            </div>
        </section>

        <Company v-if="active !== null" :wallet="this.$page.props.userWallet" :ticker="active" @close="active = null" />

    </AuthenticatedLayout>
</template>
