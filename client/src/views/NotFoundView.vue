<template>
  <div class="not-found-container">
    <div class="not-found-content">
      <div class="not-found-icon">
        <font-awesome-icon icon="book-open" class="floating" />
      </div>
      <h1>404</h1>
      <h2>Página no encontrada</h2>
      <p>Lo sentimos, el libro o sección que buscas no está disponible o no tienes permisos para acceder aquí.</p>
      
      <div class="actions">
        <RouterLink to="/dashboard" class="btn btn-primary btn-lg">
          <font-awesome-icon icon="home" class="mr-2" /> Volver al Inicio
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
// No logic needed
</script>

<style scoped>
.not-found-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--c-bg);
  padding: var(--sp-8);
  text-align: center;
}

.not-found-content {
  max-width: 500px;
  animation: slideUp var(--tr-slow);
}

.not-found-icon {
  font-size: 5rem;
  color: var(--c-primary-400);
  margin-bottom: var(--sp-6);
}

.not-found-content h1 {
  font-size: 6rem;
  font-weight: 900;
  color: var(--c-primary-900);
  margin: 0;
  line-height: 1;
}

.not-found-content h2 {
  font-size: 1.8rem;
  color: var(--c-text-primary);
  margin-bottom: var(--sp-4);
}

.not-found-content p {
  color: var(--c-text-secondary);
  font-size: 1.1rem;
  margin-bottom: var(--sp-8);
}

.floating {
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-20px); }
}

.actions {
  display: flex;
  justify-content: center;
}
</style>
