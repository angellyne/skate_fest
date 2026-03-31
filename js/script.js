class SistemaCompeticaoSkate {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.atualizarDados();
        
        // Atualizar dados a cada 5 segundos
        setInterval(() => this.atualizarDados(), 5000);
    }

    setupEventListeners() {
        const form = document.getElementById('formSkater');
        if (form) {
            form.addEventListener('submit', (e) => this.cadastrarSkater(e));
        }

        const resetBtn = document.getElementById('limparTodos');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => this.resetarCompeticao());
        }

        // Validação em tempo real das notas
        document.querySelectorAll('.input-nota').forEach(input => {
            input.addEventListener('input', (e) => this.validarNota(e.target));
        });
    }

    validarNota(input) {
        let valor = parseFloat(input.value);
        
        if (isNaN(valor)) {
            input.value = '';
            return;
        }

        if (valor < 0) input.value = 0;
        if (valor > 10) input.value = 10;
        
        // Formatar para 1 casa decimal
        if (input.value.includes('.')) {
            const partes = input.value.split('.');
            if (partes[1].length > 1) {
                input.value = parseFloat(input.value).toFixed(1);
            }
        }
    }

    cadastrarSkater(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        formData.append('action', 'cadastrar');
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.mostrarToast('✅ Skatista cadastrado com sucesso!', 'success');
                e.target.reset();
                this.atualizarDados();
                
                // Efeito especial para nota alta
                const notas = [];
                for (let i = 1; i <= 5; i++) {
                    const nota = parseFloat(formData.get(`manobra${i}`));
                    if (!isNaN(nota)) notas.push(nota);
                }
                const total = notas.reduce((a, b) => a + b, 0);
                if (total > 45) {
                    this.mostrarToast('🔥 NOTA RADICAL! 🔥', 'success');
                }
            } else {
                this.mostrarToast('❌ ' + data.errors.join(', '), 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            this.mostrarToast('Erro ao cadastrar skatista', 'error');
        });
    }

    atualizarDados() {
        // Atualizar lista de skatistas
        fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=listar'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const skaters = data.skaters;
                
                // Atualizar total de participantes
                document.getElementById('total').textContent = skaters.length;
                
                // Atualizar pódio
                this.atualizarPodium(skaters);
                
                // Atualizar lista
                this.atualizarLista(skaters);
            }
        })
        .catch(error => console.error('Erro:', error));
        
        // Atualizar estatísticas
        fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=estatisticas'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('mediaGeral').textContent = data.estatisticas.media_geral;
                document.getElementById('maiorNota').textContent = data.estatisticas.maior_nota;
            }
        })
        .catch(error => console.error('Erro:', error));
    }

    atualizarPodium(skaters) {
        const top3 = skaters.slice(0, 3);
        
        // Atualizar informações do pódio
        const podium1 = document.getElementById('podium1');
        const podium2 = document.getElementById('podium2');
        const podium3 = document.getElementById('podium3');
        
        if (top3[0]) {
            podium1.innerHTML = `<strong>${this.escapeHtml(top3[0].nome)}</strong><br>Média: ${top3[0].media_geral}`;
        } else {
            podium1.innerHTML = '';
        }
        
        if (top3[1]) {
            podium2.innerHTML = `<strong>${this.escapeHtml(top3[1].nome)}</strong><br>Média: ${top3[1].media_geral}`;
        } else {
            podium2.innerHTML = '';
        }
        
        if (top3[2]) {
            podium3.innerHTML = `<strong>${this.escapeHtml(top3[2].nome)}</strong><br>Média: ${top3[2].media_geral}`;
        } else {
            podium3.innerHTML = '';
        }
        
        // Ajustar alturas das bases baseado nas notas
        if (top3.length > 0 && skaters.length > 0) {
            const maxMedia = Math.max(...skaters.map(s => parseFloat(s.media_geral)));
            const bases = document.querySelectorAll('.podium-base');
            
            top3.forEach((skater, index) => {
                if (bases[index]) {
                    const media = parseFloat(skater.media_geral);
                    const proporcao = (media / maxMedia) * 100;
                    const alturaBase = index === 0 ? 120 : index === 1 ? 90 : 60;
                    const alturaAjustada = Math.max(40, (proporcao / 100) * alturaBase);
                    bases[index].style.height = `${alturaAjustada}px`;
                    
                    // Adicionar tooltip
                    bases[index].setAttribute('title', `${skater.nome} - ${media} pts`);
                }
            });
        } else {
            // Resetar alturas se não houver skatistas
            const bases = document.querySelectorAll('.podium-base');
            if (bases[0]) bases[0].style.height = '120px';
            if (bases[1]) bases[1].style.height = '90px';
            if (bases[2]) bases[2].style.height = '60px';
        }
    }

    atualizarLista(skaters) {
        const listaDiv = document.getElementById('listaSkaters');
        
        if (skaters.length === 0) {
            listaDiv.innerHTML = `
                <div class="item-skate" style="justify-content: center; text-align: center; opacity: 0.6;">
                    <span style="color: var(--light-blue);">🛹 Nenhum skatista cadastrado</span>
                </div>
            `;
            return;
        }
        
        listaDiv.innerHTML = skaters.map((skater, index) => `
            <div class="item-skate ${index === 0 ? 'posicao-1' : (index === 1 ? 'posicao-2' : (index === 2 ? 'posicao-3' : ''))}">
                <span class="posicao">#${index + 1}</span>
                <div class="info-skate">
                    <div class="nome-skate">${this.escapeHtml(skater.nome)}</div>
                    <div class="pais-skate">${this.escapeHtml(skater.pais)} • ${skater.idade} anos</div>
                </div>
                <span class="nota-total">${skater.media_geral}</span>
            </div>
        `).join('');
    }

    resetarCompeticao() {
        if (confirm('🔥 TEM CERTEZA? TODOS OS SKATISTAS SERÃO REMOVIDOS! 🔥')) {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=resetar'
            })
            .then(response => response.json())
            .then(() => {
                this.mostrarToast('🏆 Competição resetada com sucesso!', 'success');
                this.atualizarDados();
                
                // Resetar alturas do pódio
                const bases = document.querySelectorAll('.podium-base');
                if (bases[0]) bases[0].style.height = '120px';
                if (bases[1]) bases[1].style.height = '90px';
                if (bases[2]) bases[2].style.height = '60px';
            })
            .catch(error => {
                console.error('Erro:', error);
                this.mostrarToast('Erro ao resetar competição', 'error');
            });
        }
    }

    mostrarToast(mensagem, tipo) {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        
        toast.className = `toast ${tipo}`;
        toast.textContent = mensagem;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Inicializar quando a página carregar
document.addEventListener('DOMContentLoaded', () => {
    window.sistema = new SistemaCompeticaoSkate();
});