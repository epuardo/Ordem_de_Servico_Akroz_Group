<?php

// Carrega o autoloader do Composer e os serviços de envio de email e geração de PDF

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

use PHPMailer\PHPMailer\Exception;

use Dompdf\Dompdf;

use Dompdf\Options;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);

$dotenv->safeLoad();

$CAMINHO_LOGO_SERVIDOR = __DIR__ . '/Akrozpng.png'; 

$LOGO_CID = 'logo_akroz';

// Função para gerar o conteúdo HTML do e-mail e do PDF

function gerarConteudoHTML($dados, $arquivos, $isPdf = false, $caminho_logo = null, $logo_cid = null) {

    ob_start();

    $logo_html = '';

    if ($caminho_logo && file_exists($caminho_logo)) {

        if ($isPdf) {

            // PDF: Usamos Base64 para garantir a renderização sem problemas de caminho

            $tipo_mime = mime_content_type($caminho_logo); 

            $dados_base64 = base64_encode(file_get_contents($caminho_logo));

            $logo_src = "data:{$tipo_mime};base64,{$dados_base64}";

        } else {

            // E-mail: Usamos CID (Content-ID) para incorporação via PHPMailer

            $logo_src = "cid:{$logo_cid}";

        }

        $logo_html = '<div class="logo-container" style="text-align: center; margin-bottom: 20px;">';

        $logo_html .= '<img src="' . $logo_src . '" alt="Logo Akroz" style="max-width: 250px; height: auto;">';

        $logo_html .= '</div>';

    }



    // HTML do Endereço (Apenas para PDF)

    $endereco_manutencao_html = '

        <div class="section" style="border: 2px solid #17445f; padding: 15px; background-color: #f4f7f9;">

            <h3 style="color: #17445f; border-bottom: 2px solid #17445f; padding-bottom: 5px;">Endereço para Envio das Manutenções:</h3>

            <p style="margin-left: 10px; line-height: 1.5;">

                <strong>NTC SERVICE LTDA</strong> <br>

                Logradouro: Avenida Francisco Matarazzo <br>

                Número: 1400 <br>

                Complemento: TORRE MILANO SALA 191 <br>

                Bairro: Água Branca <br>

                Cidade: São Paulo <br>

                UF: SP <br>

                CEP: 05001100 <br>

                CPF/CNPJ: 40.430.203/0001-32

            </p>

        </div>

    ';

    ?>

    <!DOCTYPE html>

    <html>

    <head>

        <title>Ordem de Serviço de Manutenção</title>

        <style>

            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }

            .header { text-align: center; margin-bottom: 20px; }

            .section { margin-bottom: 20px; padding: 15px; border: 1px solid #eee; border-radius: 5px; }

            h3 { color: #17445f; border-bottom: 2px solid #17445f; padding-bottom: 5px; }

            ul { list-style-type: none; padding: 0; }

            li { margin-bottom: 10px; }

            .equipamento-table { width: 100%; border-collapse: collapse; margin-top: 10px; }

            .equipamento-table th, .equipamento-table td { border: 1px solid #ccc; padding: 8px; text-align: left; }

            .equipamento-table th { background-color: #f2f2f2; }

            .anexos { margin-top: 20px; }

            .anexos a { color: #17445f; text-decoration: none; }

        </style>

    </head>

     <body>

        

        <?= $logo_html ?>



        <?php if ($isPdf): ?>

            <?= $endereco_manutencao_html ?>

            <hr>

        <?php endif; ?>



        <div class="header">

            <h2>Ordem de Serviço de Manutenção</h2>

            <p><strong>Gerado em:</strong> <?= date('d/m/Y H:i:s') ?></p>

        </div>



        <div class="section">

            <h3>Informações do Cliente</h3>

            <ul>

                <li><strong>Fornecedor(a):</strong> <?= htmlspecialchars($dados['empresa_selecao']) ?></li>

                <li><strong>Nome do Cliente:</strong> <?= htmlspecialchars($dados['nome_cliente']) ?></li>

                <li><strong>CNPJ:</strong> <?= htmlspecialchars($dados['cnpj']) ?></li>

                <li><strong>Empresa:</strong> <?= htmlspecialchars($dados['empresa']) ?></li>

                <li><strong>E-mail:</strong> <?= htmlspecialchars($dados['email']) ?></li>

                <li><strong>Técnico que Autorizou o Envio:</strong> <?= htmlspecialchars($dados['tecnico']) ?></li>

                <li><strong>Protocolo de Atendimento:</strong> <?= htmlspecialchars($dados['protocolo']) ?></li>

                <li><strong>Nota Fiscal:</strong> Disponível em anexo (E-mail) / Verificar anexo (PDF)</li>

            </ul>

        </div>



       <div class="section">
            <h3>Informações dos Equipamentos</h3>
            <table class="equipamento-table">
                <thead>
                    <tr>
                        <th>Modelo</th>
                        <th>IMEI</th>
                        <th>Descrição do Problema</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($dados['equipamentos'])): ?>
                        <?php foreach ($dados['equipamentos'] as $eq): ?>
                            <tr>
                                <td><?= htmlspecialchars($eq['modelo']) ?></td>
                                <td><?= htmlspecialchars($eq['imei']) ?></td>
                                <td><?= nl2br(htmlspecialchars($eq['problema'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3">Nenhum equipamento listado.</td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($dados['equipamentos'])): ?>
                <tfoot>
                    <tr style="background-color: #f2f2f2; font-weight: bold;">
                        <td colspan="2" style="text-align: right;">Total de Equipamentos Enviados:</td>
                        <td style="text-align: left;"><?= count($dados['equipamentos']) ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        

        <?php if ($isPdf): ?>

            <div class="section anexos">

                <h3>Imagens dos Equipamentos Anexadas</h3>

                

                <?php 

                $imagens_anexadas = false;

                $imagens_por_equipamento = $arquivos['equipamentos']['tmp_name'] ?? [];

                

                foreach ($imagens_por_equipamento as $eqIndex => $imagens): 

                    if (isset($imagens['imagens']) && is_array($imagens['imagens'])):

                ?>

                        <h4>Equipamento <?= $eqIndex + 1 ?> (<?= htmlspecialchars($dados['equipamentos'][$eqIndex]['modelo'] ?? 'Modelo Desconhecido') ?>)</h4>

                        <div class="image-gallery" style="display: flex; flex-wrap: wrap; gap: 10px;">

                        <?php 

                            foreach ($imagens['imagens'] as $imgIndex => $imgTmpName):

                                if (!empty($imgTmpName) && isset($arquivos['equipamentos']['error'][$eqIndex]['imagens'][$imgIndex]) && $arquivos['equipamentos']['error'][$eqIndex]['imagens'][$imgIndex] == UPLOAD_ERR_OK):

                                    $file_content = file_get_contents($imgTmpName);

                                    $mime_type = mime_content_type($imgTmpName);

                                    $base64_image = base64_encode($file_content);

                                    $data_uri = "data:{$mime_type};base64,{$base64_image}";

                                    $imagens_anexadas = true;

                        ?>

                                    <div style="flex: 1 1 45%; max-width: 45%; margin-bottom: 10px; border: 1px solid #ccc; padding: 5px;">

                                        <img src="<?= $data_uri ?>" style="width: 100%; height: auto; display: block; max-height: 400px; object-fit: contain;">

                                        <small>Imagem <?= $imgIndex + 1 ?></small>

                                    </div>

                        <?php 

                                endif; 

                            endforeach;

                        ?>

                        </div>

                <?php

                    endif;

                endforeach;

                

                if (!$imagens_anexadas):

                ?>

                    <p>Nenhuma imagem de equipamento anexada ou as imagens foram enviadas como anexo no e-mail.</p>

                <?php endif; ?>

            </div>

        <?php endif; // Fim do bloco de imagens apenas para PDF ?>

    </body>

    </html>

    <?php

    return ob_get_clean();

}



if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $dados = $_POST;

    $arquivos = $_FILES;

    

    // Ação: ENVIAR E-MAIL (PHPMailer)

    if (isset($_POST['enviar_email']) || isset($_POST['ajax_submit'])) {

        $mail = new PHPMailer(true);



        try {

            // Configurações do Servidor

            $mail->isSMTP();

            $mail->Host='smtp.gmail.com'; 

            $mail->SMTPAuth=true;

            $email_autenticacao = $envVariables['SMTP_USERNAME'] ?? null;
            $senha_autenticacao = $envVariables['SMTP_PASSWORD'] ?? null;

            if (empty($email_autenticacao) || empty($senha_autenticacao)) {

            $email_autenticacao = 'manutencaoakrozgroup@gmail.com'; 
            $senha_autenticacao = 'fhzywhcpzsissszq'; 
        }
            
            $mail->Username   = $email_autenticacao; // Seu e-mail de envio
            $mail->Password   = $senha_autenticacao; // Sua senha ou App Password

            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

            $mail->Port= 465;

            $mail->CharSet='UTF-8';

            

            // Remetente e Destinatário
            $email_cliente=$dados['email'];

            $mail->setFrom($email_autenticacao, 'Ordem de Servico Akroz');

            $mail->addAddress($email_cliente, $dados['nome_cliente'] ?? 'Cliente');
            $mail->addAddress('manutencao@jimibrasil.com.br', 'Manutencao Jimi Brasil');

            // Logo da Akroz no E-mail

            if (file_exists($CAMINHO_LOGO_SERVIDOR)) {

                 // Adiciona a imagem como anexo oculto, associando-a ao CID

                $mail->AddEmbeddedImage($CAMINHO_LOGO_SERVIDOR, $LOGO_CID, 'Akrozpng.png');

            }



            // Gerar o conteúdo HTML do e-mail

            $mail->isHTML(true);

            $mail->Subject = 'Nova Ordem de Servico de Manutencao';

            $mail->Body     = gerarConteudoHTML($dados, $arquivos, false, $CAMINHO_LOGO_SERVIDOR, $LOGO_CID);



            // Anexos da Nota Fiscal

            if (isset($arquivos['nf']) && $arquivos['nf']['error'] == UPLOAD_ERR_OK) {

                $mail->addAttachment($arquivos['nf']['tmp_name'], $arquivos['nf']['name']);

            }



            // Anexos dos Equipamentos (Imagens)

            if (isset($arquivos['equipamentos']['tmp_name']) && is_array($arquivos['equipamentos']['tmp_name'])) {

                foreach ($arquivos['equipamentos']['tmp_name'] as $eqIndex => $imagens) {

                    if (isset($imagens['imagens']) && is_array($imagens['imagens'])) {

                        foreach ($imagens['imagens'] as $imgIndex => $imgTmpName) {

                            if (!empty($imgTmpName) && $arquivos['equipamentos']['error'][$eqIndex]['imagens'][$imgIndex] == UPLOAD_ERR_OK) {

                                $imgName = $arquivos['equipamentos']['name'][$eqIndex]['imagens'][$imgIndex];

                                $mail->addAttachment($imgTmpName, $imgName);

                            }

                        }

                    }

                }

            }

            if (!empty($dados['equipamentos'])) {
    // 1. Criamos um "arquivo" em memória
    $f = fopen('php://memory', 'r+');
    
    // 2. Adicionamos o BOM UTF-8 para o Excel reconhecer os acentos corretamente
    fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF));

    // 3. Cabeçalho das colunas (separado por ponto e vírgula, padrão Brasil)
    fputcsv($f, ['Modelo', 'IMEI', 'Descricao do Problema'], ';');

    // 4. Preenchemos com os dados da tabela
    foreach ($dados['equipamentos'] as $eq) {
        fputcsv($f, [
            $eq['modelo'],
            $eq['imei'],
            str_replace(["\r", "\n"], " ", $eq['problema']) // Remove quebras de linha para não quebrar o CSV
        ], ';');
    }

    // 5. Voltamos para o início do "arquivo" para ler o conteúdo
    rewind($f);
    $csvContent = stream_get_contents($f);
    fclose($f);

    // 6. Anexa o conteúdo direto no e-mail como um arquivo .csv
    $mail->addStringAttachment($csvContent, 'equipamentos_os.csv');
}

            $mail->send();

            

            // RETORNA JSON EM CASO DE SUCESSO

            header('Content-Type: application/json');

            echo json_encode([

                'status' => 'success',

                'message' => 'E-mail enviado com sucesso!'

            ]);

            exit;

            

        } catch (Exception $e) {

            

            // RETORNA JSON EM CASO DE ERRO

            header('Content-Type: application/json');

            echo json_encode([

                'status' => 'error',

                'message' => 'Erro ao enviar e-mail. Mailer Error: ' . $mail->ErrorInfo

            ]);

            exit;

        }

    }

    

    // Ação: EXPORTAR PDF (Dompdf)

    if (isset($_POST['exportar_pdf'])) {

        $options = new Options();

        // Permite o carregamento de imagens Base64 e caminhos absolutos

        $options->set('isHtml5ParserEnabled', true); 

        $options->set('isRemoteEnabled', true);

        $options->set('chroot', __DIR__); // Define o diretório base para resolver caminhos

        $dompdf = new Dompdf($options);



        // Gera o conteúdo HTML para o PDF

        $html = gerarConteudoHTML($dados, $arquivos, true, $CAMINHO_LOGO_SERVIDOR, $LOGO_CID);



        $dompdf->loadHtml($html);

        $dompdf->setPaper('A4', 'portrait');

        $dompdf->render();



        // Envia o PDF para o navegador

        $dompdf->stream("ordem_de_servico_" . date('Ymd_His') . ".pdf", array("Attachment" => 1));

        exit;

    }



} else {

    // Retorno padrão para acesso GET (não-AJAX)

    echo "<h1>Acesso inválido.</h1>";

}

?>