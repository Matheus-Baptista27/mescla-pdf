function log(msg, tipo = "normal"){
    const logDiv = document.getElementById('log');

    let classe = "";

    if(tipo === "ok") classe = "log-ok";
    if(tipo === "erro") classe = "log-erro";
    if(tipo === "info") classe = "log-info";

    logDiv.innerHTML += `<div class="${classe}">${msg}</div>`;
}

function processar(){
    let f = document.getElementById('funcionario').value;

    document.getElementById('log').innerHTML = "";
    document.getElementById('progress').style.width = "0%";

    let xhr = new XMLHttpRequest();
    xhr.open("GET", "processar.php?funcionario=" + f);

   xhr.onprogress = function(){
        if (!window.processandoMostrado) {
            log("⏳ Processando arquivos...", "info");
            window.processandoMostrado = true;
        }
    };

    xhr.onload = function(){
        let resposta = xhr.responseText.split("\n");

        let arquivos = [];
        let gerados = [];

        resposta.forEach(linha => {
            linha = linha.trim();

            if(!linha) return;

            if(linha.startsWith("PROCESSANDO_FUNCIONARIO:")){
                let f = linha.replace("PROCESSANDO_FUNCIONARIO:", "");
                log(`👤 Processando funcionário: ${f}`, "info");
            }
            else if(linha.startsWith("OK_ARQUIVO:")){
                arquivos.push(linha.replace("OK_ARQUIVO:", ""));
            }
            else if(linha.startsWith("OK_GERADO:")){
                gerados.push(linha.replace("OK_GERADO:", ""));
            }
            else if(linha.startsWith("ERRO_GERAR:")){
                log("❌ Erro ao gerar: " + linha.replace("ERRO_GERAR:", ""), "erro");
            }
        });

        // RESUMO
        log(`📄 ${arquivos.length} arquivos processados`, "info");
        log(`✅ ${gerados.length} PDFs gerados`, "ok");

        //FINALIZAÇÃO
        log("🎉 Processo finalizado!", "info");


        // BOTÃO
        let botao = `<button onclick="toggleDetalhes()">Ver detalhes</button>`;
        document.getElementById('log').innerHTML += botao;

        // DETALHES
        let detalhes = `<div id="detalhes" style="display:none;">`;

        arquivos.forEach(a => {
            detalhes += `<div>📄 ${a}</div>`;
        });

        gerados.forEach(g => {
            detalhes += `<div class="log-ok">✅ ${g}</div>`;
        });

        detalhes += `</div>`;

        document.getElementById('log').innerHTML += detalhes;

        document.getElementById('progress').style.width = "100%";
    };

    xhr.send();
}

function toggleDetalhes(){
    let d = document.getElementById("detalhes");

    if(d.style.display === "none"){
        d.style.display = "block";
    } else {
        d.style.display = "none";
    }
}