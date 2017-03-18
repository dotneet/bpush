package server

import (
	"github.com/labstack/echo"
	"gopkg.in/redis.v5"
)

type RedisConfig struct {
	Addr      string `yaml:"Addr"`
	Password  string `yaml:"Password"`
	KeyPrefix string `yaml:"KeyPrefix"`
	DB        int    `yaml:"DB"`
	PoolSize  int    `yaml:"PoolSize"`
}

type Config struct {
	Bind  string      `yaml:"Bind"`
	Redis RedisConfig `yaml:"Redis"`
}

type Server struct {
	config Config
	echo   *echo.Echo
	redis  *redis.Client
}

func New(config Config) *Server {
	e := echo.New()
	return &Server{config, e, nil}
}

func (server *Server) Start() {
	config := server.config

	server.redis = redis.NewClient(&redis.Options{
		Addr:     config.Redis.Addr,
		Password: config.Redis.Password,
		DB:       config.Redis.DB,
		PoolSize: config.Redis.PoolSize,
	})

	server.echo.GET("/count_receive", server.handleCountReceive)

	server.echo.Logger.Fatal(server.echo.Start(config.Bind))
}
