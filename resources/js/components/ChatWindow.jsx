import React, { useEffect, useRef, useState } from "react";
import { FaPaperPlane, FaSmile } from "react-icons/fa";
import axios from "axios";
import Message from "@/components/Message.jsx";

const ChatWindow = ({ rootUrl, chat, user }) => {
    const [messages, setMessages] = useState([]);
    const [message, setMessage] = useState("");
    const scroll = useRef();
    const webSocketChannel = `chat.${chat.id}`;

    const getMessages = async () => {
        try {
            const res = await axios.get(`${rootUrl}/chats/${chat.id}/messages`);
            setMessages(res.data);
            setTimeout(() => scroll.current?.scrollIntoView({ behavior: "smooth" }), 100);
        } catch (err) {
            console.log(err.message);
        }
    };

    const sendMessage = async (e) => {
        e.preventDefault();
        if (!message.trim()) return;

        await axios.post(`${rootUrl}/chats/${chat.id}/messages`, { text: message });
        setMessage("");
    };

    const connectWebSocket = () => {
        window.Echo.private(webSocketChannel).listen("GotMessage", async (e) => {
            await getMessages();
        });
    };

    useEffect(() => {
        getMessages();
        connectWebSocket();

        return () => {
            window.Echo.leave(webSocketChannel);
        };
    }, [chat.id]);

    return (
        <div className="chat-container">
            <div className="chat-header">
                <div className="chat-header-info">
                    <h6>{chat.name}</h6>
                    <span className="status">Online</span>
                </div>
            </div>

            <div className="chat-body">
                {messages.map((msg) => (
                    <Message key={msg.id} userId={user.id} message={msg} />
                ))}
                <div ref={scroll}></div>
            </div>

            <form onSubmit={sendMessage} className="chat-input">
                <button type="button" className="emoji-btn"><FaSmile /></button>
                <input
                    type="text"
                    placeholder="Type a message"
                    value={message}
                    onChange={(e) => setMessage(e.target.value)}
                />
                <button type="submit" className="send-btn"><FaPaperPlane /></button>
            </form>
        </div>
    );
};

export default ChatWindow;
