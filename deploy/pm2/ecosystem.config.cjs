module.exports = {
  apps: [
    {
      name: 'cyf-frontend',
      script: 'build/index.js',
      cwd: '/var/www/cyf/frontend',
      instances: 'max',
      exec_mode: 'cluster',
      autorestart: true,
      watch: false,
      max_memory_restart: '300M',
      env: {
        NODE_ENV: 'production',
        PORT: 3000,
        HOST: '127.0.0.1',
        ORIGIN: 'https://courses.fcai-azhar.edu.eg'
      }
    }
  ]
};
