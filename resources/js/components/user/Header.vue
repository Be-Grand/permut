<template>
  <nav class="navbar navbar-dark bg-user navbar-expand-md bg-faded justify-content-center">
    <router-link to="/" class="navbar-brand d-flex w-50 mr-auto">Permut</router-link>
    <button
      class="navbar-toggler"
      type="button"
      data-toggle="collapse"
      data-target="#collapsingNavbar3"
    >
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="navbar-collapse collapse w-100" id="collapsingNavbar3">
      <ul class="navbar-nav w-100 justify-content-center">
        <li class="nav-item">
          <router-link class="nav-link" to="/">Página inicial</router-link>
        </li>
        <li class="nav-item">
          <router-link to="/profile" class="nav-link">Meu perfil</router-link>
        </li>
        <li class="nav-item">
          <router-link to="/messages" class="nav-link">Mensagens</router-link>
        </li>
        <li class="nav-item">
          <router-link to="/search" class="nav-link">Pesquisar</router-link>
        </li>
        <li class="nav-item" v-if="currentUser.licensed!=1">
          <a href="#" @click.prevent="premium" class="nav-link">Premium</a>
        </li>
      </ul>
      <ul class="nav navbar-nav ml-auto w-100 justify-content-end">
        <li class="nav-item">
          <a class="nav-link" href="#">
            <i class="fas fa-bell"></i>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" @click.prevent="logout" href="#">
            <i class="fas fa-power-off"></i>
          </a>
        </li>
      </ul>
    </div>
  </nav>
</template>

<script>
import Swal from "sweetalert2";
export default {
  mounted() {
    this.$store.dispatch("fetchUser");
  },
  methods: {
    premium() {
      let loader = this.$loading.show({
        // Optional parameters
        container: this.fullPage ? null : this.$refs.formContainer,
        canCancel: false,
        loader: "dots",
        color: "#238238"
      });
      axios
        .get(`/user/premium`)
        .then(response => {
          if (!response.data.success) {
            Swal.fire("Erro!", response.data.message, "error");
          } else if (response.data.url) {
            window.location.href = response.data.url;
          }
        })
        .catch(err => {
          Swal.fire("Erro!", "Falha ao realizar a operação", "error");
        })
        .finally(() => {
          loader.hide();
        });
    },
    logout() {
      this.$store.commit("logoutUser");
      this.$router.push("/login");
    }
  },
  computed: {
    currentUser() {
      return this.$store.getters.currentUser;
    }
  }
};
</script>