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
  color: var(--dourado-claro) !important;
  text-decoration: none;
  transform: translateY(-2px);
}

.service_btn:hover::before {
  left: 0;
}

/* Estilos para botões em formulários e modais */
.btn_box .service_btn,
.btn-box2 .service_btn,
.modal-footer .service_btn,
.footer_form .service_btn {
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
  border: none;
  cursor: pointer;
  font-size: 1rem;
  min-width: 150px;
  height: 48px;
  line-height: 24px;
  text-align: center;
  box-sizing: border-box;
}

.btn_box .service_btn::before,
.btn-box2 .service_btn::before,
.modal-footer .service_btn::before,
.footer_form .service_btn::before {
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

.btn_box .service_btn:hover,
.btn-box2 .service_btn:hover,
.modal-footer .service_btn:hover,
.footer_form .service_btn:hover {
  color: var(--dourado-claro) !important;
  text-decoration: none;
  transform: translateY(-2px);
}

.btn_box .service_btn:hover::before,
.btn-box2 .service_btn:hover::before,
.modal-footer .service_btn:hover::before,
.footer_form .service_btn:hover::before {
  left: 0;
}

/* Ajustes específicos para o botão no rodapé */
.footer_form .service_btn {
  width: 100%;
  margin-top: 10px;
  min-width: auto;
}

/* Garantir consistência visual em todos os contextos */
.service_btn {
  border-radius: 25px !important;
  min-height: 48px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

/* Ajustes para botões específicos */
.btn1,
.btn-box a,
.btn-box2 a {
  border-radius: 25px;
  min-height: 48px;
  padding: 12px 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-width: 150px;
  box-sizing: border-box;
}

/* Sobrescrever estilos conflitantes do CSS principal */
.contact_section .form_container button.service_btn {
  border-radius: 25px !important;
  padding: 12px 30px !important;
  min-height: 48px !important;
  min-width: 150px !important;
  text-transform: none !important;
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 8px !important;
  background: linear-gradient(45deg, var(--dourado), var(--dourado-claro)) !important;
  border: none !important;
}

.contact_section .form_container button.service_btn:hover {
  background: linear-gradient(45deg, #0d0d0d, #8a6d1f) !important;
  color: var(--dourado-claro) !important;
  transform: translateY(-2px) !important;
}

.footer_section .footer_form button.service_btn {
  border-radius: 25px !important;
  padding: 12px 30px !important;
  min-height: 48px !important;
  background: linear-gradient(45deg, var(--dourado), var(--dourado-claro)) !important;
  border: none !important;
  text-transform: none !important;
}

.footer_section .footer_form button.service_btn:hover {
  background: linear-gradient(45deg, #0d0d0d, #8a6d1f) !important;
  color: var(--dourado-claro) !important;
  transform: translateY(-2px) !important;
}

/* Ajustes para botões em modais */
.modal-footer .service_btn {
  margin-right: 10px;
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

/* Estilos melhorados para a seção de produtos */
.products_section {
  background: linear-gradient(135deg, var(--fundo-suave) 0%, var(--fundo-card) 100%);
  padding: 80px 0;
}

.products_section .heading_container {
  margin-bottom: 60px;
}

.products_section .heading_container h2 {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--texto-forte);
  margin-bottom: 20px;
  position: relative;
}

.products_section .heading_container h2::after {
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

.products_section .heading_container p {
  font-size: 1.1rem;
  color: var(--texto-fraco);
  line-height: 1.6;
  margin-bottom: 0;
}

.products_container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 15px;
}

.product_card {
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

.product_card:hover {
  transform: translateY(-10px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.product_card::before {
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

.product_card:hover::before {
  transform: scaleX(1);
}

.product_img_container {
  position: relative;
  overflow: hidden;
  height: 220px;
  background: var(--fundo-card);
}

.product_img_container img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.product_card:hover .product_img_container img {
  transform: scale(1.1);
}

.product_overlay {
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

.product_card:hover .product_overlay {
  opacity: 1;
}

.product_overlay i {
  color: white;
  font-size: 2.5rem;
  animation: pulse 2s infinite;
}

.product_content {
  padding: 25px;
  text-align: center;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.product_title {
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

.product_price {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--dourado);
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
}

.product_price::before {
  content: 'R$';
  font-size: 1rem;
  font-weight: 500;
}

.product_btn {
  display: inline-block;
  padding: 12px 30px;
  background: linear-gradient(45deg, #25d366, #128c7e);
  color: white;
  text-decoration: none;
  border-radius: 25px;
  font-weight: 600;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.product_btn::before {
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

.product_btn:hover {
  color: var(--dourado-claro) !important;
  text-decoration: none;
  transform: translateY(-2px);
}

.product_btn:hover::before {
  left: 0;
}

.whatsapp_icon {
  margin-right: 8px;
  font-size: 1.1rem;
}

.stock_badge {
  position: absolute;
  top: 15px;
  right: 15px;
  background: linear-gradient(45deg, #28a745, #20c997);
  color: white;
  padding: 5px 12px;
  border-radius: 15px;
  font-size: 0.8rem;
  font-weight: 600;
  z-index: 2;
}

/* Estilos modernos para o mapa */
.map_container {
  position: relative;
  background: var(--fundo-card);
  border-radius: 20px;
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: all 0.4s ease;
  border: 1px solid rgba(212, 175, 55, 0.14);
  min-height: 400px;
}

.map_container:hover {
  transform: translateY(-5px);
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
}

.map_container::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--dourado), var(--dourado-claro));
  z-index: 2;
}

.map_container iframe {
  width: 100%;
  height: 100%;
  min-height: 400px;
  border: none;
  border-radius: 0 0 20px 20px;
  transition: all 0.3s ease;
}

.map_container:hover iframe {
  filter: brightness(1.05) contrast(1.1);
}

/* Overlay com informações do local */
.map_overlay {
  position: absolute;
  bottom: 20px;
  left: 20px;
  right: 20px;
  background: rgba(20, 20, 20, 0.96);
  backdrop-filter: blur(10px);
  border-radius: 15px;
  padding: 20px;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  z-index: 3;
  transform: translateY(100%);
  opacity: 0;
  transition: all 0.4s ease;
}

.map_container:hover .map_overlay {
  transform: translateY(0);
  opacity: 1;
}

.map_overlay h5 {
  color: var(--texto-forte);
  font-weight: 700;
  margin-bottom: 10px;
  font-size: 1.1rem;
}

.map_overlay p {
  color: var(--texto-fraco);
  margin-bottom: 8px;
  font-size: 0.9rem;
  line-height: 1.4;
}

.map_overlay i {
  color: var(--dourado);
  margin-right: 8px;
  width: 16px;
}

.map_directions_btn {
  display: inline-block;
  padding: 12px 30px;
  background: linear-gradient(45deg, var(--dourado), var(--dourado-claro));
  color: var(--dourado-escuro);
  text-decoration: none;
  border-radius: 25px;
  font-size: 0.9rem;
  font-weight: 600;
  transition: all 0.3s ease;
  margin-top: 10px;
  position: relative;
  overflow: hidden;
  min-width: 150px;
  height: 48px;
  line-height: 24px;
  text-align: center;
  box-sizing: border-box;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.map_directions_btn i {
  color: var(--dourado-escuro);
}

.map_directions_btn::before {
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

.map_directions_btn:hover {
  color: var(--dourado-claro) !important;
  text-decoration: none;
  transform: translateY(-2px);
}

.map_directions_btn:hover i {
  color: var(--dourado-claro);
}

.map_directions_btn:hover::before {
  left: 0;
}

/* Modern Testimonials Styles */
.modern-heading {
  text-align: center;
  margin-bottom: 60px;
}

.modern-heading h2 {
  position: relative;
  display: inline-block;
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--texto-suave);
  margin-bottom: 15px;
}

.testimonial-icon {
  color: var(--dourado);
  margin-right: 15px;
  font-size: 0.8em;
}

.testimonial-underline {
  position: absolute;
  bottom: -10px;
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 3px;
  background: linear-gradient(135deg, var(--dourado), var(--dourado-claro));
  border-radius: 2px;
}

.section-subtitle {
  color: var(--texto-fraco);
  font-size: 1.1rem;
  margin: 0;
  font-style: italic;
}

.modern-testimonials {
  margin-top: 50px;
}

.testimonial-card {
  background: var(--fundo-card);
  border-radius: 20px;
  padding: 30px;
  margin: 15px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
  position: relative;
  transition: all 0.3s ease;
  border: 1px solid var(--borda);
  overflow: hidden;
}

.testimonial-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(135deg, var(--dourado), var(--dourado-claro));
}

.testimonial-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.quote-icon {
  position: absolute;
  top: 20px;
  right: 25px;
  color: var(--dourado);
  font-size: 2rem;
  opacity: 0.3;
}

.testimonial-content {
  margin-bottom: 25px;
}

.testimonial-text {
  font-size: 1.1rem;
  line-height: 1.6;
  color: var(--texto-suave);
  font-style: italic;
  margin: 0;
  position: relative;
  z-index: 1;
}

.testimonial-author {
  display: flex;
  align-items: center;
  gap: 15px;
  padding-top: 20px;
  border-top: 1px solid var(--borda);
}

.author-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  overflow: hidden;
  border: 3px solid var(--dourado);
  box-shadow: 0 4px 10px rgba(212, 175, 55, 0.32);
}

.author-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.author-info {
  flex: 1;
}

.author-name {
  font-size: 1.2rem;
  font-weight: 600;
  color: var(--texto-suave);
  margin: 0 0 5px 0;
}

.rating {
  display: flex;
  gap: 2px;
}

.rating i {
  color: #ffc107;
  font-size: 0.9rem;
}

.modern-btn-container {
  text-align: center;
  margin-top: 50px;
}

.modern-testimonial-btn {
  background: linear-gradient(135deg, var(--dourado), var(--dourado-claro));
  color: var(--dourado-escuro);
  border: none;
  padding: 15px 30px;
  border-radius: 30px;
  font-size: 1.1rem;
  font-weight: 600;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  box-shadow: 0 6px 20px rgba(212, 175, 55, 0.32);
}

.modern-testimonial-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, var(--dourado-claro), var(--dourado));
  transition: left 0.3s ease;
  z-index: -1;
}

.modern-testimonial-btn:hover::before {
  left: 0;
}

.modern-testimonial-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
  color: var(--dourado-claro);
  text-decoration: none;
}

/* Responsive Design for Testimonials */
@media (max-width: 768px) {
  .modern-heading h2 {
    font-size: 2rem;
  }
  
  .testimonial-card {
    margin: 10px 5px;
    padding: 20px;
  }
  
  .testimonial-author {
    flex-direction: column;
    text-align: center;
    gap: 10px;
  }
  
  .author-avatar {
    width: 50px;
    height: 50px;
  }
}

/* Responsividade */
@media (max-width: 768px) {
  .services_section, .products_section {
    padding: 60px 0;
  }
  
  .services_section .heading_container h2,
  .products_section .heading_container h2 {
    font-size: 2rem;
  }
  
  .service_img_container, .product_img_container {
    height: 180px;
  }
  
  .service_content, .product_content {
    padding: 20px;
  }
  
  .service_title, .product_title {
    font-size: 1.1rem;
  }
  
  .service_price, .product_price {
    font-size: 1.3rem;
  }
  
  /* Centralização dos cards em mobile */
  .services_container .row,
  .products_container .row {
    justify-content: center !important;
  }
  
  .services_container .col-lg-3,
  .services_container .col-md-4,
  .services_container .col-sm-6,
  .products_container .col-lg-3,
  .products_container .col-md-4,
  .products_container .col-sm-6 {
    max-width: 300px;
    flex: 0 0 auto;
  }
  
  .map_container {
    min-height: 300px;
    margin-top: 30px;
  }
  
  .map_container iframe {
    min-height: 300px;
  }
  
  .map_overlay {
    bottom: 15px;
    left: 15px;
    right: 15px;
    padding: 15px;
  }
}

@media (max-width: 576px) {
  .services_section .heading_container h2,
  .products_section .heading_container h2 {
    font-size: 1.8rem;
  }
  
  .service_img_container, .product_img_container {
    height: 160px;
  }
  
  .service_btn, .product_btn {
    padding: 10px 25px;
    font-size: 0.9rem;
  }
  
  /* Centralização em telas muito pequenas */
  .services_container .col-lg-3,
  .services_container .col-md-4,
  .services_container .col-sm-6,
  .products_container .col-lg-3,
  .products_container .col-md-4,
  .products_container .col-sm-6 {
    max-width: 280px;
    margin: 0 auto 20px auto;
  }
}
/* Faixa de segmentos: barbearia x salao */
.segmento_section {
  padding: 55px 0 5px 0;
  background: var(--fundo);
}

.segmento_box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 30px;
  flex-wrap: wrap;
  background: linear-gradient(135deg, var(--fundo-card) 0%, var(--fundo-suave) 100%);
  border: 1px solid var(--borda);
  border-left: 4px solid var(--dourado);
  border-radius: 18px;
  padding: 30px 34px;
}

.segmento_texto {
  flex: 1 1 480px;
}

.segmento_selo {
  display: inline-block;
  font-size: .7rem;
  letter-spacing: .18em;
  text-transform: uppercase;
  color: var(--dourado);
  border: 1px solid var(--borda);
  border-radius: 999px;
  padding: 6px 14px;
  margin-bottom: 14px;
}

.segmento_texto h3 {
  color: var(--texto-forte);
  font-size: 1.65rem;
  margin: 0 0 10px 0;
  line-height: 1.35;
}

.segmento_texto h3 strong {
  color: var(--dourado);
  font-weight: 700;
}

.segmento_texto p {
  color: var(--texto-fraco);
  margin: 0 0 16px 0;
  max-width: 640px;
}

.segmento_tags {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.segmento_tags span {
  font-size: .8rem;
  color: var(--texto);
  background: var(--fundo-input);
  border: 1px solid var(--borda);
  border-radius: 999px;
  padding: 6px 14px;
}

.segmento_tags i {
  color: var(--dourado);
  margin-right: 6px;
}

.segmento_acao {
  flex: 0 0 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.segmento_atual {
  font-size: .72rem;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--texto-fraco);
}

.segmento_atual b {
  color: var(--dourado);
}

.segmento_btn {
  flex: 0 0 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  text-align: center;
  min-width: 250px;
  padding: 22px 28px;
  border-radius: 16px;
  background: linear-gradient(135deg, var(--dourado), var(--dourado-claro));
  color: var(--dourado-escuro) !important;
  text-decoration: none !important;
  font-weight: 700;
  box-shadow: 0 12px 30px rgba(0, 0, 0, .25);
  transition: transform .25s ease, box-shadow .25s ease;
}

.segmento_btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 16px 34px rgba(0, 0, 0, .35);
}

.segmento_btn i {
  font-size: 1.4rem;
  margin-bottom: 6px;
}

.segmento_btn span {
  font-size: 1.05rem;
}

.segmento_btn small {
  font-weight: 600;
  font-size: .72rem;
  letter-spacing: .08em;
  text-transform: uppercase;
  opacity: .85;
}

@media (max-width: 767px) {
  .segmento_box {
    padding: 24px;
  }

  .segmento_btn {
    width: 100%;
  }
}
</style>

<?php 
// Textos do carrossel conforme o segmento (barbearia ou salao)
$segmento_textos = ($segmento == 'salao') ? 'salao' : 'barbearia';
$query = $pdo->query("SELECT * FROM textos_index WHERE segmento = '$segmento_textos' ORDER BY id asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg == 0){
  $query = $pdo->query("SELECT * FROM textos_index ORDER BY id asc");
  $res = $query->fetchAll(PDO::FETCH_ASSOC);
  $total_reg = @count($res);
}
if($total_reg > 0){
 ?>
    <!-- slider section -->
    <section class="slider_section ">
      <div id="customCarousel1" class="carousel slide" data-ride="carousel">
        <div class="carousel-inner">

<?php 
for($i=0; $i < $total_reg; $i++){
  foreach ($res[$i] as $key => $value){}
  $id = $res[$i]['id'];
  $titulo = $res[$i]['titulo'];
  $descricao = $res[$i]['descricao'];

  $descricaoF = mb_strimwidth($descricao, 0, 50, "...");

  if($i == 0){
    $ativo = 'active';
  }else{
    $ativo = '';
  }
 ?>

          <div class="carousel-item <?php echo $ativo ?>">
            <div class="container ">
              <div class="row">
                <div class="col-md-6 ">
                  <div class="detail-box">
                    <h1>
                     <?php echo $titulo ?>
                    </h1>
                    <p>
                     <?php echo $descricao ?>
                    </p>
                    <div class="btn-box">
                      <a href="http://api.whatsapp.com/send?1=pt_BR&phone=<?php echo $tel_whatsapp ?>" target="_blank" class="service_btn">
                        <i class="fa fa-whatsapp" aria-hidden="true"></i>
                        Contate-nos
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
<?php 
}
 ?>

          
        </div>
        <div class="container">
          <ol class="carousel-indicators custom-indicators">
            <?php 
            for($j=0; $j < $total_reg; $j++){
              $classe_ativa = '';
              if($j == 0){
                $classe_ativa = 'class="active"';
              }
              echo '<li data-target="#customCarousel1" data-slide-to="'.$j.'" '.$classe_ativa.'></li>';
            }
            ?>
          </ol>
        </div>
      </div>
    </section>
    <!-- end slider section -->

  <?php } ?>

  </div>

  <!-- faixa: o sistema atende barbearias e saloes -->
  <section class="segmento_section">
    <div class="container">
      <div class="segmento_box">
        <div class="segmento_texto">
          <span class="segmento_selo">Um sistema, dois tipos de negocio</span>
          <h3>Este sistema funciona para <strong>barbearias</strong> e <strong>salões de beleza</strong></h3>
          <p>Agenda online, cadastro de clientes, controle de estoque, comissões e relatórios: a estrutura é a mesma. O tema do site se adapta ao seu negócio.</p>
          <div class="segmento_tags">
            <span><i class="fa fa-scissors" aria-hidden="true"></i> Barbearia</span>
            <span><i class="fa fa-magic" aria-hidden="true"></i> Salão de beleza</span>
          </div>
        </div>
        <div class="segmento_acao">
        <span class="segmento_atual">Tema atual: <b><?php echo $segmento == 'salao' ? 'Salão de beleza' : 'Barbearia'; ?></b></span>
        <?php if($segmento == 'salao'){ ?>
        <a class="segmento_btn" href="?segmento=barbearia">
          <i class="fa fa-scissors" aria-hidden="true"></i>
          <span>Você tem uma barbearia?</span>
          <small>Clique aqui e veja o tema</small>
        </a>
        <?php }else{ ?>
        <a class="segmento_btn" href="?segmento=salao">
          <i class="fa fa-magic" aria-hidden="true"></i>
          <span>Você tem um salão de beleza?</span>
          <small>Clique aqui e veja o tema</small>
        </a>
        <?php } ?>
        </div>
      </div>
    </div>
  </section>

  <!-- services section -->
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
  <!-- services section ends -->

  <!-- about section -->
  <section class="about_section ">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-6 px-0">
          <div class="img-box ">
            <img src="images/<?php echo $imagem_sobre ?>" class="box_img" alt="about img">
          </div>
        </div>
        <div class="col-md-5">
          <div class="detail-box ">
            <div class="heading_container">
              <h2 class="">
                Sobre Nós
              </h2>
            </div>
            <p class="detail_p_mt">
              <?php echo $texto_sobre ?>
            </p>
            <a href="http://api.whatsapp.com/send?1=pt_BR&phone=<?php echo $tel_whatsapp ?>" class="service_btn">
              <i class="fa fa-whatsapp"></i> Mais Informações
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- about section ends -->

  <!-- products section -->
  <?php 
  $query = $pdo->query("SELECT * FROM produtos where estoque > 0 and valor_venda > 0 and segmento = '$segmento' ORDER BY id desc limit 8");
  $res = $query->fetchAll(PDO::FETCH_ASSOC);
  $total_reg = @count($res);
  if($total_reg > 0){ 
  ?>

  <section class="products_section">
    <div class="container-fluid">
      <div class="heading_container heading_center">
        <h2>Nossos Produtos</h2>
        <p class="col-lg-8 px-0">
          Confira nossa seleção exclusiva de produtos de qualidade premium para cuidados masculinos.
        </p>
      </div>
      
      <div class="products_container">
        <div class="row justify-content-center">
          <?php 
          for($i=0; $i < $total_reg; $i++){
            foreach ($res[$i] as $key => $value){}
            
            $id = $res[$i]['id'];
            $nome = $res[$i]['nome'];   
            $valor = $res[$i]['valor_venda'];
            $foto = $res[$i]['foto'];
            $descricao = $res[$i]['descricao'];
            $valorF = number_format($valor, 2, ',', '.');
          ?>
          
          <div class="col-lg-3 col-md-4 col-sm-6 mb-4 d-flex">
            <div class="product_card">
              <div class="product_img_container">
                <img src="sistema/painel/img/produtos/<?php echo $foto ?>" alt="<?php echo $nome ?>" title="<?php echo $descricao ?>">
                <div class="product_overlay">
                  <i class="fa fa-shopping-cart"></i>
                </div>
                <div class="stock_badge">
                  <i class="fa fa-check"></i> Em Estoque
                </div>
              </div>
              <div class="product_content">
                <h5 class="product_title"><?php echo $nome ?></h5>
                <div class="product_price"><?php echo $valorF ?></div>
                <a target="_blank" href="http://api.whatsapp.com/send?1=pt_BR&phone=<?php echo $tel_whatsapp ?>&text=Olá, gostaria de saber mais informações sobre o produto <?php echo $nome ?>" class="product_btn">
                  <i class="fa fa-whatsapp whatsapp_icon"></i>Comprar no WhatsApp
                </a>
              </div>
            </div>
          </div>
          
          <?php } ?>    
        </div>
      </div>
      
      <div class="btn-box" style="text-align: center; margin-top: 40px;">
        <a href="produtos" style="display: inline-block; padding: 15px 40px; background: linear-gradient(45deg, var(--dourado), var(--dourado-claro)); color: var(--dourado-escuro); text-decoration: none; border-radius: 25px; font-weight: 600; transition: all 0.3s ease;">
          Ver mais Produtos
        </a>
      </div>
    </div>
  </section>

  <?php } ?>
  <!-- products section ends -->

  <!-- contact section -->
  <section class="contact_section layout_padding">
    <div class="container">
      <div class="heading_container">
        <h2>
          Contate-nos
        </h2>
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="form_container">
            <form id="form-email">
              <div>
                <input type="text" name="nome" placeholder="Seu Nome" required/>
              </div>
              <div>
                <input type="text" name="telefone" id="telefone" placeholder="Seu Telefone" required />
              </div>
              <div>
                <input type="email" name="email" placeholder="Seu Email" required />
              </div>
              <div>
                <input type="text" name="mensagem" class="message-box" placeholder="Mensagem" required />
              </div>
              <div class="btn_box">
                <button class="service_btn" type="submit">
                  <i class="fa fa-paper-plane"></i> Enviar
                </button>
              </div>
            </form>

            <br><div id="mensagem"></div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="map_container">
           <?php echo $mapa ?>
           <div class="map_overlay">
             <h5><i class="fa fa-map-marker"></i>Nossa Localização</h5>
             <p><i class="fa fa-home"></i><?php echo $endereco_sistema ?></p>
             <p><i class="fa fa-phone"></i><?php echo $telefone_fixo_sistema ?></p>
             <p><i class="fa fa-clock-o"></i>Seg-Sex: 8h às 18h | Sáb: 8h às 16h</p>
             <a href="https://www.google.com/maps/dir//<?php echo urlencode($endereco_sistema) ?>" target="_blank" class="map_directions_btn">
               <i class="fa fa-map-marker"></i> Como Chegar
             </a>
           </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!-- end contact section -->

  <!-- client section -->
<?php 
$query = $pdo->query("SELECT * FROM comentarios where ativo = 'Sim' and segmento = '$segmento' ORDER BY id asc");
$res = $query->fetchAll(PDO::FETCH_ASSOC);
$total_reg = @count($res);
if($total_reg > 0){ 
 ?>
  <section class="client_section layout_padding-bottom">
    <div class="container">
      <div class="heading_container modern-heading">
        <h2>
          <span class="testimonial-icon"><i class="fa fa-quote-left"></i></span>
          Depoimento dos nossos Clientes
          <span class="testimonial-underline"></span>
        </h2>
        <p class="section-subtitle">Veja o que nossos clientes dizem sobre nossos serviços</p>
      </div>
      <div class="client_container modern-testimonials">
        <div class="carousel-wrap">
          <div class="owl-carousel client_owl-carousel">

            <?php 
            for($i=0; $i < $total_reg; $i++){
          foreach ($res[$i] as $key => $value){}
 
          $id = $res[$i]['id'];
          $nome = $res[$i]['nome'];   
           $texto = $res[$i]['texto'];
           $foto = $res[$i]['foto'];   
             ?>

            <div class="item">
              <div class="testimonial-card">
                <div class="quote-icon">
                  <i class="fa fa-quote-right"></i>
                </div>
                <div class="testimonial-content">
                  <p class="testimonial-text">
                    "<?php echo $texto ?>"
                  </p>
                </div>
                <div class="testimonial-author">
                  <div class="author-avatar">
                    <img src="sistema/painel/img/comentarios/<?php echo $foto ?>" alt="<?php echo $nome ?>">
                  </div>
                  <div class="author-info">
                    <h5 class="author-name"><?php echo $nome ?></h5>
                    <div class="rating">
                      <i class="fa fa-star"></i>
                      <i class="fa fa-star"></i>
                      <i class="fa fa-star"></i>
                      <i class="fa fa-star"></i>
                      <i class="fa fa-star"></i>
                    </div>
                  </div>
                </div>
              </div>
            </div>


<?php } ?>

          </div>
        </div>
      </div>
    </div>

     <div class="btn-box2 modern-btn-container">
        <a href="" data-toggle="modal" data-target="#modalComentario" class="service_btn modern-testimonial-btn">
         <i class="fa fa-comment"></i> Inserir Depoimento
        </a>
      </div>

  </section>

<?php } ?>

  <!-- end client section -->

  <?php require_once("rodape.php") ?>

  <!-- Modal Depoimentos -->
  <div class="modal fade" id="modalComentario" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Inserir Depoimento
                   </h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="margin-top: -20px">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        
        <form id="form">
      <div class="modal-body">

          <div class="row">
            <div class="col-md-12">
              <div class="form-group">
                <label for="exampleInputEmail1">Nome</label>
                <input type="text" class="form-control" id="nome_cliente" name="nome" placeholder="Nome" required>    
              </div>  
            </div>
            <div class="col-md-12">

              <div class="form-group">
                <label for="exampleInputEmail1">Texto <small>(Até 500 Caracteres)</small></label>
                <textarea maxlength="500" class="form-control" id="texto_cliente" name="texto" placeholder="Texto Comentário" required> </textarea>   
              </div>  
            </div>
          </div>

            <div class="row">
              <div class="col-md-8">            
                <div class="form-group"> 
                  <label>Foto</label> 
                  <input class="form-control" type="file" name="foto" onChange="carregarImg();" id="foto">
                </div>            
              </div>
              <div class="col-md-4">
                <div id="divImg">
                  <img src="sistema/painel/img/comentarios/sem-foto.jpg"  width="80px" id="target">                  
                </div>
              </div>

            </div>

            <input type="hidden" name="id" id="id">
             <input type="hidden" name="cliente" value="1">

          <br>
          <small><div id="mensagem-comentario" align="center"></div></small>
        </div>

        <div class="modal-footer">      
          <button type="submit" class="service_btn"><i class="fa fa-plus"></i> Inserir</button>
        </div>
      </form>

      </div>
    </div>
  </div>

<script type="text/javascript">
  
$("#form-email").submit(function () {

    event.preventDefault();
    var formData = new FormData(this);

    $.ajax({
        url: 'ajax/enviar-email.php',
        type: 'POST',
        data: formData,

        success: function (mensagem) {
            $('#mensagem').text('');
            $('#mensagem').removeClass()
            if (mensagem.trim() == "Enviado com Sucesso") {
               $('#mensagem').addClass('text-success')
                $('#mensagem').text(mensagem)

            } else {

                $('#mensagem').addClass('text-danger')
                $('#mensagem').text(mensagem)
            }


        },

        cache: false,
        contentType: false,
        processData: false,

    });

});

</script>

<script type="text/javascript">
  function carregarImg() {
    var target = document.getElementById('target');
    var file = document.querySelector("#foto").files[0];
    
        var reader = new FileReader();

        reader.onloadend = function () {
            target.src = reader.result;
        };

        if (file) {
            reader.readAsDataURL(file);

        } else {
            target.src = "";
        }
    }
</script>

<script type="text/javascript">
  
$("#form").submit(function () {

    event.preventDefault();
    var formData = new FormData(this);


    $.ajax({
        url: 'sistema/painel/paginas/comentarios/salvar.php',
        type: 'POST',
        data: formData,

        success: function (mensagem) {
            $('#mensagem-comentario').text('');
            $('#mensagem-comentario').removeClass()
            if (mensagem.trim() == "Salvo com Sucesso") {
            
            $('#mensagem-comentario').addClass('text-success')
                $('#mensagem-comentario').text('Comentário Enviado para Aprovação!')
                 $('#nome_cliente').val('');
                  $('#texto_cliente').val('');

            } else {

                $('#mensagem-comentario').addClass('text-danger')
                $('#mensagem-comentario').text(mensagem)
            }


        },

        cache: false,
        contentType: false,
        processData: false,

    });

});

</script>