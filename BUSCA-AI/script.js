/*
 * BUSCA AÍ — interações
 * O site continua totalmente utilizável sem JavaScript: a classe "js" (adicionada no <head>)
 * só ativa os estados iniciais das animações e o menu recolhível em telas pequenas.
 */
document.addEventListener("DOMContentLoaded", () => {

    const reduzirMovimento = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    /* ---------- Revelação suave ao rolar ---------- */
    const seletoresRevelar = [
        ".inicio-texto", ".card-destaque", ".processo", ".etapa", ".beneficio", ".chamada",
        ".funciona-topo", ".etapa-funciona", ".funciona-diferencial", ".funciona-chamada",
        ".sobre-topo", ".sobre-bloco", ".valor", ".sobre-diferencial", ".sobre-chamada",
        ".card-resumo", ".ultimas-devolucoes", ".boas-vindas",
        ".login-info", ".login-card", ".contato-info", ".contato-formulario",
        ".formulario-info", ".formulario-card", ".perfil-card", ".editar-card", ".senha-card",
        ".lista-devolucoes", ".lista-painel", ".dados-devolucao", ".alterar-status",
        ".devolucao-detalhes", ".historico-devolucao"
    ].join(",");

    const alvos = document.querySelectorAll(seletoresRevelar);

    // Atraso em cascata entre irmãos do mesmo grupo.
    alvos.forEach((el) => {
        const irmaos = Array.from(el.parentElement.children).filter((i) => i.matches(seletoresRevelar));
        el.style.setProperty("--atraso", `${Math.min(irmaos.indexOf(el), 6) * 70}ms`);
    });

    if (reduzirMovimento || !("IntersectionObserver" in window)) {
        alvos.forEach((el) => el.classList.add("visivel"));
    } else {
        alvos.forEach((el) => el.classList.add("revelar"));

        const observador = new IntersectionObserver((entradas, obs) => {
            entradas.forEach((entrada) => {
                if (entrada.isIntersecting) {
                    entrada.target.classList.add("visivel");
                    obs.unobserve(entrada.target);
                }
            });
        }, { threshold: 0.1, rootMargin: "0px 0px -30px 0px" });

        alvos.forEach((el) => observador.observe(el));
    }

    /* ---------- Menu móvel ---------- */
    const cabecalho = document.querySelector(".cabecalho");
    const botaoMenu = document.querySelector(".menu-toggle");
    const menu = document.getElementById("menu-principal");

    if (cabecalho && botaoMenu && menu) {
        const definirMenu = (aberto) => {
            cabecalho.classList.toggle("menu-aberto", aberto);
            botaoMenu.setAttribute("aria-expanded", String(aberto));
            botaoMenu.setAttribute("aria-label", aberto ? "Fechar menu" : "Abrir menu");
        };

        botaoMenu.addEventListener("click", () => {
            definirMenu(botaoMenu.getAttribute("aria-expanded") !== "true");
        });

        document.addEventListener("keydown", (evento) => {
            if (evento.key === "Escape" && botaoMenu.getAttribute("aria-expanded") === "true") {
                definirMenu(false);
                botaoMenu.focus();
            }
        });

        menu.addEventListener("click", (evento) => {
            if (evento.target.closest("a")) definirMenu(false);
        });
    }

    /* ---------- Sombra do cabeçalho ao rolar ---------- */
    if (cabecalho) {
        const atualizarCabecalho = () => cabecalho.classList.toggle("rolado", window.scrollY > 8);
        atualizarCabecalho();
        window.addEventListener("scroll", atualizarCabecalho, { passive: true });
    }

    /* ---------- Mostrar / ocultar senha (não altera o valor enviado) ---------- */
    document.querySelectorAll("[data-mostrar-senha]").forEach((botao) => {
        botao.addEventListener("click", () => {
            const campo = document.getElementById(botao.dataset.mostrarSenha);
            if (!campo) return;

            const mostrar = campo.type === "password";
            campo.type = mostrar ? "text" : "password";
            botao.textContent = mostrar ? "Ocultar" : "Mostrar";
            botao.setAttribute("aria-pressed", String(mostrar));
            botao.setAttribute("aria-label", mostrar ? "Ocultar senha" : "Mostrar senha");
        });
    });

    /* ---------- Confirmação opcional em ações importantes ---------- */
    document.querySelectorAll("[data-confirmar]").forEach((elemento) => {
        elemento.addEventListener("click", (evento) => {
            if (!confirm(elemento.dataset.confirmar || "Deseja realmente continuar?")) {
                evento.preventDefault();
            }
        });
    });

    /* ---------- Evita envio duplo dos formulários ---------- */
    document.querySelectorAll("form").forEach((formulario) => {
        formulario.addEventListener("submit", () => {
            const enviar = formulario.querySelector('button[type="submit"]');
            if (enviar && formulario.checkValidity()) {
                setTimeout(() => { enviar.disabled = true; }, 0);
            }
        });
    });

    // Ao voltar pelo histórico (bfcache) o botão não pode ficar travado.
    window.addEventListener("pageshow", (evento) => {
        if (evento.persisted) {
            document.querySelectorAll('form button[type="submit"]').forEach((b) => { b.disabled = false; });
        }
    });
});
