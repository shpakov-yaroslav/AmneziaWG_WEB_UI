<footer>
    <div class="footer-content">
        <div class="footer-left">
            <p><i class="fas fa-shield-alt"></i> AmneziaWG Web Panel v1.0.0</p>
            <p>Управление VPN сервером через веб-интерфейс</p>
        </div>
        
        <div class="footer-right">
            <p>© <?php echo date('Y'); ?> Все права защищены</p>
            <p>
                <a href="https://github.com/amnezia-vpn/amneziawg" target="_blank" style="color: white; text-decoration: none;">
                    <i class="fab fa-github"></i> AmneziaWG
                </a>
            </p>
        </div>
    </div>
</footer>

<style>
    .footer-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    .footer-left p, .footer-right p {
        margin: 5px 0;
    }
    
    @media (max-width: 768px) {
        .footer-content {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }
    }
</style>
