document.addEventListener("DOMContentLoaded", function() {
    const deleteButtons = document.querySelectorAll('.delete-ingredient');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            confirmDeletion.call(this);
        });
    });

    // Gestion de l'ajout d'ingrédients
    document.getElementById('add-ingredient').addEventListener('click', function () {
        const container = document.getElementById('ingredients-container');
        const prototype = container.parentNode.dataset.prototype;
        const index = container.children.length; 

        const newForm = prototype.replace(/__name__/g, index);
        const div = document.createElement('div');
        div.classList.add('col-3'); 
        div.innerHTML = `
            <div class="ingredient-group border rounded p-3 shadow-sm">
                ${newForm}
                <button type="button" class="delete-ingredient btn btn-danger mt-2">Supprimer</button>
            </div>
        `;
        container.appendChild(div); 

        // Re-attacher le gestionnaire d'événements à chaque nouveau bouton "Supprimer"
        div.querySelector('.delete-ingredient').addEventListener('click', function() {
            confirmDeletion.call(this);
        });
    });
});

    function confirmDeletion() {
        if (confirm("Êtes-vous sûr de vouloir supprimer cet élément ?")) {
            const ingredient = this.closest('.col-3'); 
            if (ingredient) {
                ingredient.remove(); 
            }
        }
    }