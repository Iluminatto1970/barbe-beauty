<?php require_once("cabecalho.php") ?>
<style type="text/css">
	.sub_page .hero_area {
  min-height: auto;
}

/* Estilos melhorados para a seção de serviços */
.services_section {
  background: linear-gradient(135deg, var(--fundo-suave) 0%, var(--fundo-card) 100%);
  padding: 80px 0;
}

.services_section .heading_container {
  margin-bottom: 60px;
}

.services_section .heading_container h2 {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--texto-forte);
  margin-bottom: 20px;
  position: relative;
}

.services_section .heading_container h2::after {
  content: '';
  position: absolute;
  bottom: -10px;
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 4px;
  background: linear-gradient(90deg, var(--dourado), var(--dourado-claro));
  border-radius: 2px;
}

.services_section .heading_container p {
  font-size: 1.1rem;
  color: var(--texto-fraco);
  line-height: 1.6;
  margin-bottom: 0;
}

.services_container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 15px;
  display: flex;
  justify-content: center;
}

.services_container .row {
  width: 100%;
  max-width: 1200px;
  margin: 0 auto;
}

.service_card {
  background: var(--fundo-card);
  border-radius: 20px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  transition: all 0.4s ease;
  overflow: hidden;
  margin-bottom: 30px;
  position: relative;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.service_card:hover {
  transform: translateY(-10px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.service_card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--dourado), var(--dourado-claro));
  transform: scaleX(0);
  transition: transform 0.4s ease;
}

.service_card:hover::before {
  transform: scaleX(1);
}

.service_img_container {
  position: relative;
  overflow: hidden;
  height: 220px;
  background: var(--fundo-card);
}

.service_img_container img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.service_card:hover .service_img_container img {
  transform: scale(1.1);
}

.service_overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(45deg, rgba(212, 175, 55, 0.92), rgba(240, 205, 127, 0.92));
  opacity: 0;
  transition: opacity 0.4s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.service_card:hover .service_overlay {
  opacity: 1;
}

.service_overlay i {
  color: white;
  font-size: 2.5rem;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

.service_content {
  padding: 25px;
  text-align: center;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.service_title {
  font-size: 1.3rem;
  font-weight: 600;
  color: var(--texto-forte);
  margin-bottom: 15px;
  line-height: 1.4;
  min-height: 2.6rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.service_price {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--dourado);
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
}

.service_price::before {
  content: 'R$';
  font-size: 1rem;
  font-weight: 500;
}

.service_btn {
  display: inline-block;
  padding: 12px 30px;
  background: linear-gradient(45deg, var(--dourado), var(--dourado-claro));
  color: var(--dourado-escuro);
  text-decoration: none;
  border-radius: 25px;
  font-weight: 600;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.service_btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(45deg, #0d0d0d, #8a6d1f);
  transition: left 0.3s ease;
  z-index: -1;
}

.service_btn:hover {
  color: var(--dourado-claro);
  text-decoration: none;
  transform: translateY(-2px);
}

.service_btn:hover::before {
  left: 0;
}

.category_tags {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 10px;
  margin-bottom: 40px;
}

.category_tag {
  padding: 8px 16px;
  background: rgba(212, 175, 55, 0.14);
  color: var(--dourado);
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: 500;
  border: 1px solid rgba(212, 175, 55, 0.22);
}

/* Responsividade */
@media (max-width: 768px) {
  .services_section {
    padding: 60px 0;
  }
  
  .services_section .heading_container h2 {
    font-size: 2rem;
  }
  
  .service_img_container {
    height: 180px;
  }
  
  .service_content {
    padding: 20px;
  }
  
  .service_title {
    font-size: 1.1rem;
  }
  
  .service_price {
    font-size: 1.3rem;
  }
  
  /* Centralização dos cards em mobile */
  .services_container .row {
    justify-content: center !important;
  }
  
  .services_container .col-lg-3,
  .services_container .col-md-4,
  .services_container .col-sm-6 {
    max-width: 300px;
    flex: 0 0 auto;
  }
}

@media (max-width: 576px) {
  .services_section .heading_container h2 {
    font-size: 1.8rem;
  }
  
  .service_img_container {
    height: 160px;
  }
  
  .service_btn {
    padding: 10px 25px;
    font-size: 0.9rem;
  }
  
  /* Centralização em telas muito pequenas */
  .services_container .col-lg-3,
  .services_container .col-md-4,
  .services_container .col-sm-6 {
    max-width: 280px;
    margin: 0 auto 20px auto;
  }
}
</style>

</div>

<section class="services_section">
  <div class="container-fluid">
    <div class="heading_container heading_center">
      <h2>Nossos Serviços</h2>
      <p class="col-lg-8 px-0">
        <?php 
        $query = $pdo->query("SELECT * FROM cat_servicos where segmento = '$segmento' ORDER BY id asc");
        $res = $query->fetchAll(PDO::FETCH_ASSOC);
        $total_reg = @count($res);
        if($total_reg > 0){ 
        ?>
        <div class="category_tags">
        <?php
        for($i=0; $i < $total_reg; $i++){
          foreach ($res[$i] as $key => $value){}
          $id = $res[$i]['id'];
          $nome = $res[$i]['nome'];
          echo '<span class="category_tag">' . $nome . '</span>';
        }
        ?>
        </div>
        <?php
        }
        
        $query = $pdo->query("SELECT * FROM servicos where ativo = 'Sim' and segmento = '$segmento' ORDER BY id asc");
        $res = $query->fetchAll(PDO::FETCH_ASSOC);
        $total_reg = @count($res);
        if($total_reg > 0){ 
        ?>
        <?php echo ($segmento == 'salao') ? 'Descubra nossos serviços de beleza com qualidade excepcional e atendimento personalizado.' : 'Descubra nossos serviços profissionais de barbearia com qualidade excepcional e atendimento personalizado.'; ?>
      </p>
    </div>
    
    <div class="services_container">
      <div class="row justify-content-center">
        <?php 
        for($i=0; $i < $total_reg; $i++){
          foreach ($res[$i] as $key => $value){}
          
          $id = $res[$i]['id'];
          $nome = $res[$i]['nome'];   
          $valor = $res[$i]['valor'];
          $foto = $res[$i]['foto'];
          $valorF = number_format($valor, 2, ',', '.');
        ?>
        
        <div class="col-lg-3 col-md-4 col-sm-6 mb-4 d-flex">
          <div class="service_card">
            <div class="service_img_container">
              <img src="sistema/painel/img/servicos/<?php echo $foto ?>" alt="<?php echo $nome ?>">
              <div class="service_overlay">
                <i class="fa fa-cut"></i>
              </div>
            </div>
            <div class="service_content">
              <h5 class="service_title"><?php echo $nome ?></h5>
              <div class="service_price"><?php echo $valorF ?></div>
              <a href="agendamentos" class="service_btn">
                <i class="fa fa-calendar"></i> Agendar Serviço
              </a>
            </div>
          </div>
        </div>
        
        <?php } ?>    
      </div>
    </div>
    
    <?php } ?>
  </div>
</section>



  <!-- product section ends -->




 
   <?php require_once("rodape.php") ?>