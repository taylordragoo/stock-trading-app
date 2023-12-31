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
            loading: false
        }
    },
    methods: {
        async searchStocks() {
            if (this.searchQuery && this.searchQuery.length > 0) {
                try {
                    this.loading = true;
                    axios.defaults.headers.common = {};
                    const response = await axios.get(`/api/search/${this.searchQuery}`)
                        .then(response => {
                            this.searchResults = response.data;
                            console.log(this.searchResults);
                            this.loading = false
                        })
                        .catch(error => {
                            console.error('Error searching stocks:', error);
                            this.loading = false
                        });

                } catch (error) {
                    console.error('Error searching stocks:', error);
                    this.loading = false
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
    <Head title="Search" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Search</h2>
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
                                <th scope="col" class="px-4 py-3">Locale</th>
                                <th scope="col" class="px-4 py-3">Currency</th>
                                <th scope="col" class="px-4 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                            </thead>
                            <tbody v-if="searchResults.length">
                                <tr v-if="loading">
                                    <td colspan="12" class="text-center">
                                        <p class="py-5">
                                            <svg width="60" height="60" viewBox="0 0 135 135" xmlns="http://www.w3.org/2000/svg" fill="#910000"><path d="M67.447 58c5.523 0 10-4.477 10-10s-4.477-10-10-10-10 4.477-10 10 4.477 10 10 10zm9.448 9.447c0 5.523 4.477 10 10 10 5.522 0 10-4.477 10-10s-4.478-10-10-10c-5.523 0-10 4.477-10 10zm-9.448 9.448c-5.523 0-10 4.477-10 10 0 5.522 4.477 10 10 10s10-4.478 10-10c0-5.523-4.477-10-10-10zM58 67.447c0-5.523-4.477-10-10-10s-10 4.477-10 10 4.477 10 10 10 10-4.477 10-10z"><animateTransform attributeName="transform" type="rotate" from="0 67 67" to="-360 67 67" dur="2.5s" repeatCount="indefinite"/></path><path d="M28.19 40.31c6.627 0 12-5.374 12-12 0-6.628-5.373-12-12-12-6.628 0-12 5.372-12 12 0 6.626 5.372 12 12 12zm30.72-19.825c4.686 4.687 12.284 4.687 16.97 0 4.686-4.686 4.686-12.284 0-16.97-4.686-4.687-12.284-4.687-16.97 0-4.687 4.686-4.687 12.284 0 16.97zm35.74 7.705c0 6.627 5.37 12 12 12 6.626 0 12-5.373 12-12 0-6.628-5.374-12-12-12-6.63 0-12 5.372-12 12zm19.822 30.72c-4.686 4.686-4.686 12.284 0 16.97 4.687 4.686 12.285 4.686 16.97 0 4.687-4.686 4.687-12.284 0-16.97-4.685-4.687-12.283-4.687-16.97 0zm-7.704 35.74c-6.627 0-12 5.37-12 12 0 6.626 5.373 12 12 12s12-5.374 12-12c0-6.63-5.373-12-12-12zm-30.72 19.822c-4.686-4.686-12.284-4.686-16.97 0-4.686 4.687-4.686 12.285 0 16.97 4.686 4.687 12.284 4.687 16.97 0 4.687-4.685 4.687-12.283 0-16.97zm-35.74-7.704c0-6.627-5.372-12-12-12-6.626 0-12 5.373-12 12s5.374 12 12 12c6.628 0 12-5.373 12-12zm-19.823-30.72c4.687-4.686 4.687-12.284 0-16.97-4.686-4.686-12.284-4.686-16.97 0-4.687 4.686-4.687 12.284 0 16.97 4.686 4.687 12.284 4.687 16.97 0z"><animateTransform attributeName="transform" type="rotate" from="0 67 67" to="360 67 67" dur="8s" repeatCount="indefinite"/></path></svg>
                                        </p>
                                    </td>
                                </tr>
                                <tr v-if="!loading" v-for="stock in searchResults" :key="stock.ticker" class="border-b">
                                    <th scope="row" class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">{{ stock.name }}</th>
                                    <td class="px-4 py-3">{{ stock.ticker }}</td>
                                    <td class="px-4 py-3">{{ stock.locale }}</td>
                                    <td class="px-4 py-3">{{ stock.currency_name }}</td>
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
