document.addEventListener('DOMContentLoaded', () => {
    // visibilidad del contenedor de filtros
    const filterButton = document.getElementById('filterButton');
    const filtersContainer = document.getElementById('filtersContainer');

    filterButton.addEventListener('click', () => {
        filtersContainer.classList.toggle('hidden');
    });

    // Manejar el clic del botón de menú para redirigir al usuario
    const menuButton = document.querySelector('button[aria-label="Menu"]');
    menuButton.addEventListener('click', () => {
        window.location.href = 'component/menu.php';
    });
});